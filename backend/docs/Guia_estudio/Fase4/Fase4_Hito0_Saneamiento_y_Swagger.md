# 🔧 Guía de Estudio — Fase 4 · Hito 0
## Saneamiento técnico y documentación de la API

---

## 🎯 ¿Qué hicimos en este hito?

Dos cosas, ambas de "limpieza y preparación" antes de construir el núcleo clínico:

1. **Cazamos un bug invisible** que ensuciaba TODAS las respuestas de la API.
2. **Instalamos Swagger**, una herramienta que documenta y permite probar la API desde el navegador.

Piénsalo como preparar la cocina antes de cocinar: no es el plato principal, pero sin eso el plato sale mal.

---

## 🐛 Parte A — El misterio del corchete fantasma

### El síntoma

Cada respuesta de la API se veía así:

```json
[                          ← ¿de dónde salió esto?
  {
    "success": true,
    "data": { ... }
  }
}
```

Ese `[` aparecía **abierto pero nunca cerrado**. Un JSON válido jamás se ve así.

### Por qué era grave

Nicoll (frontend) tuvo que escribir un **parche** en su código para cortar ese carácter antes de poder leer la respuesta. Es como si el cartero te entregara todas las cartas con una hoja en blanco pegada adelante, y tú tuvieras que arrancarla cada vez.

Y lo peor: **cada endpoint nuevo que construyéramos en la Fase 4 habría tenido el mismo problema.**

### Cómo lo encontramos

No adivinamos. Usamos comandos de diagnóstico:

```powershell
# Busca archivos .php que NO empiecen exactamente con <?php
Get-ChildItem -Recurse -Include *.php -Path app,routes,config,bootstrap,database |
  ForEach-Object { $c = Get-Content $_.FullName -Raw; if ($c -notmatch '^<\?php') { $_.FullName } }
```

Resultado: **un solo archivo culpable** → `config/sanctum.php`

Y al mirarlo de cerca:

```
[<?php[LF][LF]use Illuminate\Cookie\Middleware\EncryptCo
 ↑
 ¡AHÍ ESTABA!
```

### 💡 El concepto clave: todo fuera de `<?php` se imprime

En PHP, **cualquier cosa que esté fuera de las etiquetas `<?php ... ?>` se envía directamente al navegador**, antes que cualquier otra cosa.

```php
[            ← esto se imprime LITERALMENTE
<?php
// esto es código, no se imprime
echo "hola";
```

> **Analogía:** imagina que PHP es un actor leyendo un guion. Todo lo que está dentro de `<?php ... ?>` son *instrucciones de escena* (no se dicen en voz alta). Todo lo que está afuera es *diálogo* (sí se dice). Alguien había escrito un `[` en la zona de diálogo por error, y el actor lo decía en voz alta al inicio de cada función.

### Por qué afectaba a TODOS los endpoints

`config/sanctum.php` es el archivo de configuración de la autenticación. **Se carga en cada petición** que pase por `auth:sanctum` — o sea, casi todas tus rutas.

### La solución

1. Borrar el `[` para que el archivo empiece exactamente en `<?php`
2. Limpiar la caché de configuración: `php artisan config:clear`

**Resultado:** respuestas limpias, empezando directo en `{`. ✅

---

## 📖 Parte B — Swagger: la API que se documenta sola

### ¿Qué problema resuelve?

Antes de Swagger, cuando Nicoll necesitaba usar un endpoint, tenía que preguntarte:
- "¿Qué campos le mando?"
- "¿Qué me devuelve si sale bien?"
- "¿Y si sale mal?"
- "¿Necesita token?"

Cada duda = un mensaje de WhatsApp = tiempo perdido para ambas.

> **Analogía:** Swagger es como el **menú de un restaurante con fotos y descripciones**. Sin menú, tendrías que preguntarle al mesero qué trae cada plato. Con menú, lo ves, lo entiendes y hasta puedes pedirlo de inmediato.

### Las dos piezas

| Pieza | Qué es |
|---|---|
| **OpenAPI** | El *estándar* (el idioma) para describir una API: qué rutas tiene, qué reciben, qué devuelven |
| **Swagger UI** | La *página web bonita* que lee esa descripción y la muestra con botones para probar |

Y el paquete que conecta ambos con Laravel se llama **`l5-swagger`**.

### 🚧 El obstáculo que encontramos

Al principio, escribimos la documentación con el estilo **clásico**, dentro de comentarios:

```php
/**
 * @OA\Info(
 *     title="SenaVida API"
 * )
 */
```

Y Swagger insistía: `Required @OA\Info() not found` — como si no existiera.

**Probamos 4 hipótesis** antes de dar con la correcta:

| # | Hipótesis | Resultado |
|---|---|---|
| 1 | Falta el `use OpenApi\Annotations as OA;` | ❌ No era |
| 2 | La versión de swagger-php es muy nueva, hay que bajarla | ❌ Imposible: l5-swagger 11 *requiere* la 6.x |
| 3 | Falta el paquete `doctrine/annotations` | ❌ No era (y además está abandonado) |
| 4 | **Cambió la sintaxis: ahora usa atributos de PHP 8** | ✅ ¡ERA ESO! |

### 💡 El concepto clave: atributos vs anotaciones

PHP 8 introdujo los **atributos**, que son *código real* en vez de comentarios:

| Antes (anotaciones) | Ahora (atributos) |
|---|---|
| Dentro de `/** ... */` | Con `#[ ... ]`, fuera de comentarios |
| `use OpenApi\Annotations as OA;` | `use OpenApi\Attributes as OA;` |
| `@OA\Info(title="...")` | `#[OA\Info(title: '...')]` |
| Se usa `=` para valores | Se usa `:` para valores |
| PHP los ignora (son texto) | PHP los valida (son código) |

> **Analogía:** las anotaciones eran como **notas adhesivas pegadas** al código — fáciles de despegar o ignorar. Los atributos son como **etiquetas soldadas**: forman parte de la estructura, y si están mal, PHP mismo te avisa.

### ⚠️ La regla de oro de los atributos

Un atributo debe estar **pegado directamente** al método que describe:

```php
✅ CORRECTO:
    /**
     * Comentario normal
     */
    #[OA\Post(...)]
    public function login() { }

❌ INCORRECTO:
    #[OA\Post(...)]
    /**
     * ¡Este comentario rompe la conexión!
     */
    public function login() { }
```

**Orden correcto:** comentario → atributo → método

---

## 🏗️ Lo que construimos

### 1. La "portada" — en `Controller.php`

```php
#[OA\Info(...)]           ← título, versión, descripción de la API
#[OA\Server(...)]         ← contra qué URL se prueban los endpoints
#[OA\SecurityScheme(...)] ← cómo se ve un token válido (activa el botón "Authorize")
```

Se puso en el `Controller` base porque **todos los demás controladores heredan de él** — un solo punto de verdad.

### 2. Los 10 endpoints documentados

| Sección | Endpoints |
|---|---|
| **Autenticacion** | `POST /auth/login` · `GET /auth/me` 🔒 · `POST /auth/logout` 🔒 |
| **Catalogos** | `GET/POST /organizations` 🔒 · `GET/POST /health-centers` 🔒 · `GET/POST /units` 🔒 |
| **Usuarios** | `POST /users` 🔒 |

### Anatomía de un endpoint documentado

```php
#[OA\Post(
    path: '/auth/login',              // la ruta (sin /api/v1, ya está en Server)
    summary: 'Iniciar sesion',        // título corto que se ve en la lista
    description: 'Autentica a...',    // explicación larga
    tags: ['Autenticacion'],          // agrupa endpoints por categoría
    security: [['bearerAuth' => []]], // 🔒 requiere token (el login NO lo lleva)
    requestBody: new OA\RequestBody(  // qué datos hay que enviar
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', example: 'admin@test.com'),
            ]
        )
    ),
    responses: [                      // qué puede devolver
        new OA\Response(response: 200, description: 'Login exitoso'),
        new OA\Response(response: 422, description: 'Credenciales incorrectas'),
    ]
)]
```

**Detalle importante:** el `login` es el **único sin `security`**, porque es la puerta de entrada — es el endpoint que te *da* el token, no puede exigirte uno.

---

## 🧪 Comandos que aprendiste

```powershell
# Generar/actualizar la documentación (correr después de cada cambio)
php artisan l5-swagger:generate

# Limpiar caché de configuración
php artisan config:clear

# Verificar sintaxis de un archivo PHP sin ejecutarlo
php -l ruta/al/archivo.php

# Ver información de un paquete instalado
composer show nombre/paquete
```

**Dónde ver el resultado:**
- Interfaz visual → `http://127.0.0.1:8000/api/documentation`
- JSON crudo → `http://127.0.0.1:8000/docs?api-docs.json`

---

## 🎓 Las 4 lecciones grandes

### 1. Depurar es descartar, no adivinar
Probamos 4 hipótesis con evidencia antes de dar con la correcta. Cada "no era" nos acercó al "sí era". Eso es trabajo real de desarrollo, no fracaso.

### 2. Un carácter puede romper todo
Un solo `[` fuera de lugar contaminó 10 endpoints. En programación, la precisión importa más que el volumen de código.

### 3. La documentación no es burocracia, es infraestructura
Swagger no solo "se ve bonito": elimina una fuente enorme de fricción entre frontend y backend, y hace obvio cuándo una respuesta no tiene la forma esperada.

### 4. Los estándares cambian, y hay que leerlos
El paso de anotaciones a atributos no está en tutoriales viejos. Cuando algo "debería funcionar" y no funciona, vale la pena revisar si la herramienta cambió.

---

## 📌 Pendientes anotados

1. **Avisarle a Nicoll** que quite el parche del `[` en `apiClient.ts` — ya no es necesario, y ahora *causaría* un bug (cortaría el `{` real de la respuesta).
2. **Corregir `VITE_API_URL`** en el `.env` del frontend: apunta a `senavida-backend.test` en vez de `127.0.0.1:8000`.

---

## ▶️ Lo que viene

Con el backend limpio y documentado, cambia el orden de trabajo respecto al plan original. En vez de saltar directo al núcleo clínico, el equipo decidió **construir primero el CRUD completo del módulo administrativo**, por dos razones:

1. **La rúbrica del EVA3** exige que al menos un módulo tenga CRUD completo (Crear, Ver, Editar, Eliminar) con frontend y backend integrados y funcionando.
2. **Integración temprana:** subir avances frecuentes para que el frontend los pruebe sobre la marcha, en vez de descubrir incompatibilidades al final.

### Plan de hitos actualizado

| Hito | Qué construye | Estado |
|:---:|---|:---:|
| **0** | Saneamiento técnico + Swagger | ✅ Completado |
| **A** | CRUD completo de **Usuarios** | ⏭️ Siguiente |
| **B** | CRUD completo de **Organizaciones** | Pendiente |
| **C** | CRUD completo de **Centros de Salud** | Pendiente |
| **D** | CRUD completo de **Unidades** | Pendiente |
| **1** | Modelo **Paciente** + contactos de emergencia | Pendiente |
| **2** | **CTA** (Código Temporal de Atención) | Pendiente |
| **3** | **Sesión Médica** | Pendiente |
| **4** | **Middleware de sesión activa** | Pendiente |

En los hitos A–D, cada entidad recibe las operaciones que le faltan: **ver uno solo**, **listar**, **editar** y **desactivar** (soft delete usando el campo `is_active`, para no perder historial clínico).

Después de completarlos, se retoma el núcleo clínico con el **Hito 1 — Modelo Paciente**.

---

*Guía de estudio · Proyecto SeñaVida · Fase 4 · Hito 0*
