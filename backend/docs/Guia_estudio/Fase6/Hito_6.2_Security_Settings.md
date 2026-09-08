# Guía de Estudio — Hito 6.2: Parámetros de seguridad (`security_settings`)

## 1. Objetivo del hito

Sacar el límite de intentos fallidos del CTA del código fuente y convertirlo en una configuración editable por cada centro de salud, de verdad conectada al comportamiento del sistema — no solo un valor guardado.

## 2. ¿Por qué era necesario?

Antes de este hito, `TemporaryAccessCodeController.php` tenía escrito `'max_attempts' => 3` directamente en el código. Cambiar ese número exigía editar el código fuente y volver a desplegar — nada de eso es responsabilidad de un administrador de hospital sin conocimientos técnicos.

## 3. Decisión importante: qué NO construir todavía

Este es el aprendizaje más valioso del hito. Se evaluó construir también `session_timeout_minutes` (minutos de inactividad antes de cerrar una atención), pero se decidió **no hacerlo** en este momento.

**Razón:** no existe en el sistema ningún mecanismo que revise cuánto tiempo lleva inactiva una atención. Construir el campo de todas formas habría creado una **configuración decorativa** — el admin cambiaría un número, vería que se guardó, pero nada en el sistema lo usaría. Es el mismo tipo de problema que un campo fuera de `$fillable`: algo que parece funcionar pero no hace nada.

**Principio aplicado:** es mejor entregar menos funcionalidad, pero que esté completamente conectada de punta a punta, que entregar "más" con una parte hueca. Un campo sin lógica que lo consuma no es una función a medias — es una ilusión de función.

## 4. Conceptos clave aprendidos

### 4.1 `firstOrCreate` — evitar el estado "no configurado"

Sin esta herramienta, tendríamos que preguntar primero "¿existe configuración para este centro?", y si no existe, crearla con un paso separado. `firstOrCreate` hace ambas cosas en una sola línea:

```php
$setting = SecuritySetting::firstOrCreate(
    ['health_center_id' => $admin->health_center_id],
    ['cta_max_attempts' => 3]
);
```

Busca una fila que coincida con el primer array. Si la encuentra, la devuelve. Si no, la crea usando ambos arrays combinados. Así, el endpoint nunca responde "configuración no encontrada" — siempre hay algo sensato que mostrar, incluso la primera vez que alguien lo consulta.

### 4.2 El orden de las operaciones importa: autorizar antes de tocar la base de datos

**El bug encontrado:** el código intentaba `firstOrCreate` **antes** de revisar si el usuario tenía permiso. Como `super_admin` no tiene centro asociado (`health_center_id` es `null`), la creación de la fila violaba la restricción `NOT NULL` de la columna, y la base de datos misma lanzaba un error — que se propagó como un `500 Internal Server Error` en vez del `403` que correspondía.

**La lección:** las verificaciones baratas y sin efectos secundarios (como comprobar un rol) deben ir **antes** que las operaciones que tocan la base de datos. Invertir el orden no es solo un detalle de estilo — cambia qué tipo de error recibe el usuario, y un `500` filtra detalles internos del sistema (nombres de tablas, rutas de archivos) que un `403` nunca debería mostrar.

```php
// Orden correcto:
if ($admin->role !== 'admin_institucional') {
    return response()->json([...], 403); // barato, sin tocar BD
}

$setting = SecuritySetting::firstOrCreate(...); // ya sabemos que es seguro
```

### 4.3 Cuándo la Policy no puede usarse tal cual

En el `UpdateSecuritySettingRequest`, el primer intento fue:

```php
return $this->user()->can('update', $this->route('security_setting'));
```

Esto falla porque `/security-settings` **no tiene un ID en la URL** — a diferencia de `/pictograms/{pictogram}`, este endpoint siempre opera sobre "mi propio centro", derivado del token. Como no hay parámetro de ruta que Laravel pueda resolver a un modelo, `$this->route('security_setting')` devuelve `null`, y la Policy no tiene nada que autorizar.

**La solución:** cuando un endpoint nunca acepta un ID externo (por diseño, siempre opera sobre el propio contexto del usuario autenticado), no hay necesidad de una Policy completa — basta con verificar el rol directamente, porque el escenario que la Policy protegería (alguien accediendo al recurso de *otra* persona) no puede ocurrir.

### 4.4 Diseño de la conexión: copiar el valor, no consultarlo en cada uso

El límite de intentos no se lee "en vivo" cada vez que se valida un código — se copia dentro del propio CTA en el momento en que se genera. Esto preserva una decisión ya tomada en la Fase 4: cada código conserva la regla con la que nació. Si el admin cambia el límite de 3 a 5, esa modificación afecta solo a los códigos generados **después** del cambio, nunca a los que ya estaban circulando.

## 5. Endpoints construidos

| Endpoint | Verbo | Qué hace |
|---|---|---|
| `/security-settings` | `GET` | Muestra (y autocrea si no existe) la configuración del propio centro |
| `/security-settings` | `PUT` | Actualiza `ctaMaxAttempts` (rango válido: 1–10) |

## 6. Cómo se verificó

La prueba más importante de este hito no fue solo probar el mantenedor — fue confirmar que el cambio de configuración **de verdad afecta** un CTA nuevo:

1. Se cambió el límite a `5` vía `PUT`.
2. Se generó un CTA nuevo para un paciente real.
3. Se verificó directamente en la base de datos (Tinker) que ese CTA nació con `max_attempts = 5`, no con el `3` anterior.

Esta cadena completa (configurar → generar → verificar en la base de datos) es la diferencia entre un mantenedor real y uno decorativo.

## 7. Resumen — qué aprendí

1. No toda funcionalidad "prometida" debe construirse de inmediato — si no hay lógica que consuma un dato, ese dato es decorativo. Mejor entregar menos, pero funcional de punta a punta.
2. `firstOrCreate` resuelve en una línea el problema de "¿existe o no existe todavía?".
3. El orden de las operaciones en un método importa: las verificaciones baratas van antes que las que tienen efectos secundarios (como escribir en la base de datos).
4. No todo endpoint necesita una Policy completa — si por diseño nunca puede operar sobre el recurso de otra persona (no acepta un ID externo), basta con verificar el rol.
5. Verificar una configuración no es solo comprobar que se guardó — es comprobar que **cambia el comportamiento real** del sistema en el punto donde se usa.
