# Guía de Estudio — Hito 6.3: Consulta de auditoría (`audit-logs`)

## 1. Objetivo del hito

Construir `GET /audit-logs`: el endpoint que permite a un `admin_institucional` consultar qué pasó en su propio centro, con filtros y paginación — y ampliar la infraestructura de auditoría que ya existía para que fuera de verdad completa.

## 2. ¿Por qué era necesaria?

La tabla `audit_logs` y su observador automático existían desde la Fase 0, registrando eventos silenciosamente. Pero sin un endpoint de consulta, toda esa información quedaba atrapada en la base de datos — nadie podía verla. Además, al revisar el contrato en detalle, aparecieron dos brechas que había que cerrar antes de que la auditoría fuera confiable de verdad.

## 3. Las dos brechas encontradas antes de escribir código

### 3.1 Faltaban columnas obligatorias

El contrato exige que todo evento de auditoría tenga `healthCenterId` y `severity`. La tabla no tenía ninguna de las dos. La ausencia de `healthCenterId` era la más grave: sin ella, un admin podría ver la actividad de **todos** los hospitales del sistema, no solo el suyo — rompiendo el principio de aislamiento por centro que el resto del proyecto respeta estrictamente.

### 3.2 Modelos importantes no se auditaban

`Pictogram`, `PictogramCategory`, `SecuritySetting` y `Consent` nunca dispararon un evento de auditoría. En particular, la revocación de un consentimiento — que el contrato marca explícitamente como un evento **crítico** — no dejaba ningún rastro.

**Lección:** un sistema de auditoría "que existe" no es lo mismo que un sistema de auditoría "que funciona". Antes de construir el endpoint de consulta, hubo que asegurarse de que hubiera algo completo que consultar.

## 4. Conceptos clave aprendidos

### 4.1 Centralizar una regla de negocio en un solo lugar

En vez de que cada modelo "supiera" su propia severidad, toda la lógica de clasificación vive en un único método del `AuditLogObserver`:

```php
private function resolveSeverity(Model $model, string $action, array $changes): string
{
    if ($model instanceof Consent && ($changes['status'] ?? null) === 'revoked') {
        return 'critical';
    }
    if ($model instanceof TemporaryAccessCode && ($changes['status'] ?? null) === 'blocked') {
        return 'warning';
    }
    if ($action === 'updated' && in_array(get_class($model), self::STRUCTURAL_MODELS, true)) {
        return 'warning';
    }
    return 'info';
}
```

**Ventaja:** si mañana cambia el criterio de qué es crítico, se edita un solo archivo. Si la regla estuviera dispersa (cada modelo decidiendo su propia severidad), un cambio de criterio significaría tocar N archivos distintos, con el riesgo de olvidar alguno.

### 4.2 Resolver un dato con una cadena de estrategias, no un caso especial por tabla

Determinar a qué centro pertenece un evento no tiene una sola respuesta uniforme: `User` y `Unit` tienen su propia columna; `Consent` no tiene columna propia pero sí una relación a `MedicalSession`, de la cual se puede heredar el centro. En vez de escribir una función distinta por cada modelo, se probó una cadena de estrategias en orden, deteniéndose en la primera que aplique:

```php
private function resolveHealthCenterId(Model $model): ?string
{
    if (array_key_exists('health_center_id', $model->getAttributes())) {
        return $model->health_center_id;
    }
    if ($model instanceof HealthCenter) {
        return $model->id;
    }
    if (method_exists($model, 'medicalSession') && $model->medicalSession) {
        return $model->medicalSession->health_center_id;
    }
    $actor = Auth::user();
    return $actor instanceof \App\Models\User ? $actor->health_center_id : null;
}
```

Esto evita duplicar lógica y deja claro, leyendo de arriba hacia abajo, cuál es la prioridad de cada fuente de información.

### 4.3 Un actor puede no ser del tipo que esperabas

**El bug encontrado:** `Auth::id()` asume que quien está autenticado es un `User`. Pero en SeñaVida, un `Patient` también puede estar autenticado (con un token distinto, acotado a una sola atención) — y `Patient` no implementa completamente la interfaz que `Auth::id()` necesita. El resultado fue un `500 Internal Server Error` en una acción legítima: un paciente revocando su propio consentimiento.

**La lección:** cuando un sistema tiene más de un tipo de identidad autenticada (aquí, `User` y `Patient`), cualquier código que use `Auth::` de forma genérica debe verificar **de qué tipo** es el actor antes de asumir que tiene ciertos métodos o propiedades.

```php
$actor = Auth::user();
$userId = $actor instanceof \App\Models\User ? $actor->id : null;
```

Si el actor no es del tipo esperado, el evento se registra igual — simplemente sin ese dato — en vez de que toda la operación falle.

### 4.4 Un log fallido en silencio es peor que un log fallido ruidoso

Cuando el bug ocurrió, el `Consent` **sí se revocó correctamente** en la base de datos — el error saltó *después*, al intentar registrar el evento de auditoría. Esto significa que, sin la corrección, habría datos clínicos modificados **sin rastro de auditoría**, y encima con una respuesta de error confusa para el cliente. Verificar el estado en la base de datos tras un error (en vez de asumir que "no pasó nada") fue la única forma de descubrir esto.

## 5. Endpoint construido

| Endpoint | Verbo | Filtros disponibles |
|---|---|---|
| `/audit-logs` | `GET` | `action`, `severity`, `userId`, `occurredAtFrom/To`, `sort`, paginación |

**Limitación documentada:** el contrato sugiere un filtro `patientId`, no implementado en este hito porque `audit_logs` no tiene una columna directa de paciente (usa una relación polimórfica genérica). Agregarlo requeriría un cambio de esquema fuera del alcance actual.

## 6. Cómo se verificó

1. Se generó un evento real (`info`): crear un pictograma.
2. Se generó un evento real (`critical`): revocar un consentimiento — con el token de un **paciente**, no de personal, exponiendo el bug de `Auth::id()`.
3. Se confirmó el aislamiento por centro: el mismo filtro (`severity=critical`) devolvió `total: 0` para el admin de un centro distinto, y `total: 1` para el admin del centro correcto.

## 7. Resumen — qué aprendí

1. Un sistema de auditoría que "existe" pero no cubre todos los modelos sensibles, o no clasifica severidad con criterio, es tan poco confiable como no tener auditoría.
2. Centralizar una regla de negocio en un solo método facilita mantenerla y evita inconsistencias.
3. Resolver un dato ambiguo (como "el centro de este evento") con una cadena de estrategias en orden es más limpio que un caso especial por cada tipo de entidad.
4. Cuando un sistema tiene más de un tipo de identidad autenticada, el código genérico debe verificar el tipo antes de asumir qué métodos tiene disponible.
5. Verificar el estado real en la base de datos después de un error es la única forma de saber si un fallo fue solo en la respuesta HTTP, o si dejó datos a medio camino.
