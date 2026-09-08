# Guía de Estudio — Hito 6.4: `/admin/stats` y exportación firmada

## 1. Objetivo del hito

Calcular tres estadísticas reales para el panel de administración (`activeUsers`, `apiRequests`, `auditCoverage`) y construir la exportación firmada de la bitácora de auditoría (`POST /audit-logs/export`), cerrando así la Fase 6 completa.

## 2. ¿Por qué era necesario?

El contrato es inusualmente franco sobre el estado del prototipo: *"las tres cifras están escritas a mano en el código y nunca cambian."* Un panel de administración con números falsos es peor que no tener panel — genera confianza en información que no refleja la realidad.

## 3. Conceptos clave aprendidos

### 3.1 Un middleware global cuenta lo que nadie más ve

**El problema:** no existía ninguna forma de saber cuánto tráfico recibe la API. Ningún controller, por sí solo, puede ver el conjunto completo de peticiones — cada uno solo sabe de las suyas.

**La solución:** un middleware que se ejecuta en **todas** las rutas, incrementando un contador en caché:

```php
public function handle(Request $request, Closure $next): Response
{
    Cache::add(self::CACHE_KEY, 0, now()->addHours(24));
    Cache::increment(self::CACHE_KEY);
    return $next($request);
}
```

**Por qué caché y no base de datos:** un `INSERT` en cada petición sería lento a gran escala. La caché está diseñada para contadores de alta frecuencia.

**Por qué `Cache::increment()` y no `Cache::get()` + `Cache::put()`:** `increment()` es **atómico** — garantiza que dos peticiones simultáneas no se "pisen" al sumar. Sin esa garantía, dos peticiones al mismo tiempo podrían leer el mismo valor inicial y ambas sumarle 1, resultando en +1 en vez de +2.

### 3.2 El orden de los middlewares cambia qué se mide

**El bug encontrado:** el middleware, colocado con `append` (al final de la fila), nunca llegaba a ejecutarse en rutas protegidas cuando el token era inválido — porque `Authenticate:sanctum` cortaba el flujo antes.

**La decisión:** mover el middleware al **inicio** de la fila (`prepend`), para que cuente **todo** el tráfico, incluidas las peticiones rechazadas. Es una decisión de diseño, no solo un fix técnico: define qué significa "tráfico de la API" para tu sistema — todo intento de conexión, no solo los exitosos.

### 3.3 Medir el propio sistema con reflexión, en vez de escribir un número

**El reto:** ¿cómo calcular "cobertura de auditoría" sin inventar un porcentaje?

**La solución:** usar `ReflectionClass` de PHP para preguntarle a cada modelo, en tiempo real, si tiene el atributo `#[ObservedBy(AuditLogObserver::class)]` puesto:

```php
$reflection = new ReflectionClass($modelClass);
$attributes = $reflection->getAttributes(ObservedBy::class);
```

**Por qué esto es mejor que escribir `100` a mano:** si en el futuro alguien agrega un modelo sensible nuevo y olvida conectar el observador, este número **baja automáticamente**. Es una auto-verificación genuina del estado del código, no una promesa que nadie revisa.

**Lo que este proceso reveló:** al construir esta métrica con honestidad, hubo que revisar primero qué modelos *no* estaban cubiertos — y se encontró que `MedicalSession`, la entidad central de todo el dominio clínico, nunca se había conectado al observador. Medir algo con rigor a veces obliga a encontrar y corregir huecos que estaban ahí desde antes.

### 3.4 Consultar también es una acción que se audita

**El requisito del contrato:** *"la consulta de la bitácora también se audita."* No solo modificar datos dispara un evento — **mirarlos** también debe dejar rastro, porque saber quién revisó qué información es en sí mismo un dato sensible.

**El problema técnico:** el `AuditLogObserver` solo reacciona a eventos de Eloquent (crear, editar, borrar). Una simple lectura (`GET`) no dispara ninguno de esos eventos.

**La solución:** registrar el evento manualmente, dentro del propio controlador, para las dos acciones de acceso a la bitácora (`viewed_audit_log`, `exported_audit_log`).

### 3.5 Una firma HMAC demuestra integridad, no autoría legal

**Concepto:** HMAC-SHA256 es una huella criptográfica calculada con una clave secreta. Si el contenido cambia una sola letra después de firmarse, la huella ya no coincide.

**Decisión de diseño importante:** la clave de firma (`AUDIT_EXPORT_SIGNING_KEY`) se generó **separada** de `APP_KEY` (la clave general de Laravel). Si `APP_KEY` se rota alguna vez (por ejemplo, tras sospecha de filtración), eso no debe invalidar silenciosamente las firmas de exportaciones ya generadas — son responsabilidades distintas que no deben mezclarse.

**Limitación documentada, no fingida:** una firma HMAC prueba que el archivo no fue alterado; no es una firma digital con validez legal de no repudio (eso requeriría un certificado). Se documentó explícitamente esta diferencia como decisión académica (D-30), dejando la firma con certificado para una fase futura.

### 3.6 Verificar una firma exige los bytes exactos, no una copia manual

**Lo que pasó al intentar verificar a mano:** copiar y pegar el JSON desde una captura de pantalla, y volver a calcular el HMAC sobre esa copia, dio una firma **distinta** a la original — no porque el sistema estuviera fallando, sino porque el copy-paste puede introducir diferencias mínimas de espaciado, orden o codificación de caracteres que arruinan la comparación exacta que un HMAC necesita.

**La verificación correcta:** capturar la respuesta real del servidor byte por byte (`curl.exe -o archivo.json`), y recalcular el HMAC leyendo ese archivo directamente con PHP — sin ningún paso manual de por medio. El resultado coincidió exactamente.

**La lección más importante del hito:** esto no es un defecto de la firma — es la firma haciendo exactamente su trabajo. Cualquier alteración, por mínima que sea, debe romper la coincidencia. Una firma que "más o menos" coincide no sirve para nada.

## 4. Endpoints construidos

| Endpoint | Verbo | Qué hace |
|---|---|---|
| `/admin/stats` | `GET` | Tres cifras calculadas en vivo, exclusivo de `admin_institucional` |
| `/audit-logs/export` | `POST` | Exportación completa firmada con HMAC-SHA256 |

## 5. Cómo se verificó

1. Se confirmó que `apiRequests` sube con cada petición real, incluidas las no autenticadas.
2. Se confirmó `403` para `super_admin` en `/admin/stats`.
3. Se generó una exportación real y se verificó su firma de forma completamente independiente, en un script separado, comparando byte a byte.
4. Se confirmó que tanto consultar como exportar la bitácora generan su propio registro de auditoría.

## 6. Resumen — qué aprendí

1. Medir algo del propio sistema (como cobertura de auditoría) con reflexión de código es más honesto y más duradero que escribir un número fijo.
2. El orden de los middlewares no es un detalle menor — define exactamente qué se mide o se protege.
3. Un contador de alta frecuencia debe vivir en caché, con operaciones atómicas, no en la base de datos.
4. Auditar la lectura de datos sensibles es tan importante como auditar su modificación — requiere código explícito porque los frameworks no lo hacen "gratis".
5. Una firma criptográfica separa claramente "esto no fue alterado" de "esto tiene validez legal de autoría" — son garantías distintas, y hay que ser honesto sobre cuál se está ofreciendo.
6. Verificar una firma exige trabajar con los bytes exactos del archivo real — cualquier copia manual, por cuidadosa que sea, puede introducir diferencias invisibles que rompen la comparación.
