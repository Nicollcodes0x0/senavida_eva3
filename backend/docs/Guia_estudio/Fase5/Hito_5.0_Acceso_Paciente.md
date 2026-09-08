# Hito 5.0 · Acceso del paciente (token derivado del CTA)

**Fase:** 5 — Chat y Consentimientos
**Fecha:** 26 de agosto, 2026
**Ratifica:** A-03 del contrato (autenticación del paciente)

---

## 1. ¿Qué problema resuelve este hito?

Hasta antes de este hito, **no existía ninguna forma de que un paciente se autenticara** ante la API. Cuatro endpoints de la Fase 5 son exclusivos del paciente (aprobar, rechazar y revocar un consentimiento, y confirmar un mensaje). Sin un mecanismo de autenticación, ninguno de esos endpoints se podía siquiera probar — el sistema no tenía forma de saber "esta petición viene realmente de Ana, la paciente".

La solución: el paciente **canjea su código de atención (CTA)** — el mismo código que Admisión ya usó para abrir su atención — por un **Bearer token de Sanctum**, acotado únicamente a esa atención.

---

## 2. Conceptos nuevos aprendidos

### 2.1 Token con *abilities* (habilidades)

Un token normal de Sanctum es como la llave maestra de un conserje: abre todas las puertas que su rol permite. Un token con *abilities* es como la llave de un huésped de hotel: abre **una sola habitación**.

```php
$patient->createToken('patient-portal', ["session:{$session->id}"]);
```

Ese token queda grabado con el permiso `session:{id}`. Más adelante, cualquier middleware puede comprobar `$token->can("session:{$id}")` antes de dejar pasar una petición — así, aunque alguien robe el token de un paciente, no le sirve para leer la atención de otro paciente.

### 2.2 Relación polimórfica de tokens (`tokenable`)

Sanctum no guarda los tokens pensando en un solo tipo de modelo. La tabla `personal_access_tokens` tiene columnas `tokenable_type` y `tokenable_id`, lo que le permite colgar tokens de **cualquier modelo** — un `User` de staff o, como en este hito, un `Patient`.

Esto es posible gracias a una decisión que ya habían tomado en Hito 0: usar `uuidMorphs('tokenable')` en la migración, en vez del `morphs()` por defecto (que asume IDs numéricos autoincrementales, incompatibles con las UUID que usa todo el proyecto).

Para que un modelo pueda **portar** tokens, solo necesita el trait `HasApiTokens`:

```php
class Patient extends Model
{
    use HasApiTokens, HasUuids;
    // ...
}
```

### 2.3 Middleware de segregación de identidad

Con Sanctum, `$request->user()` puede devolver ahora un `Patient` o un `User`, dependiendo de quién sea dueño del token. Esto es peligroso si no se controla: una Policy escrita pensando en `$user->role` **reventaría con un error 500** si alguna vez recibe un `Patient` (que no tiene esa columna).

La solución fue crear dos middlewares "guardia" que actúan **antes** de que la petición llegue a cualquier Policy:

- `EnsurePatientToken` — exige que el portador sea `Patient`. Protege las rutas exclusivas del paciente.
- `EnsureStaffToken` — exige que el portador sea `User`. Protege todas las rutas clínicas y administrativas.

```php
if (! $request->user() instanceof Patient) {
    throw new AuthorizationException('WRONG_TOKEN_TYPE|Este recurso es exclusivo del paciente.');
}
```

Se reutilizó el mismo formato `"CODIGO|mensaje"` que ya usan las Policies del proyecto (partido en `bootstrap/app.php`), en vez de usar `abort()` genérico — así el frontend siempre recibe un `code` legible, nunca solo un texto libre.

### 2.4 Orden de ejecución de middlewares

Los middlewares de un grupo de rutas se ejecutan **de afuera hacia adentro**. Esto importa mucho: si una ruta específica queda **anidada dentro** de un grupo protegido por `staff.only`, el grupo bloquea la petición antes de que la ruta interna llegue a evaluarse — sin importar qué middleware tenga esa ruta interna.

Por eso, la ruta `/auth/patient/logout` tuvo que sacarse del grupo grande de `staff.only` y declararse aparte, con su propio middleware `['auth:sanctum', 'patient.only']`.

---

## 3. Decisión de diseño ratificada

**Problema detectado:** el contrato original decía que el redeem debía validar que el código "no estuviera usado". Pero en Fase 4 se decidió que **T2 se disolvió dentro de S1**: al abrir la atención, Admisión ya consume el código (`temporary_access_codes.status = 'consumed'`).

**Decisión tomada:** el redeem del paciente **no** valida contra `temporary_access_codes`, sino contra `medical_sessions.cta_code`, exigiendo que la sesión siga abierta (`$session->status->isOpen()`).

**Por qué:** el código ya cumplió su función de "abrir la puerta" en el Paso de Admisión. Para el paciente, ese mismo código pasa a significar otra cosa: "esta es mi conversación actual". Buscarlo en la sesión (no en el código original) resuelve también la vigencia gratis — si la atención se cierra, el paciente pierde acceso automáticamente.

---

## 4. Archivos construidos

| # | Archivo | Responsabilidad |
|---|---|---|
| 1 | `app/Models/Patient.php` | Trait `HasApiTokens` — habilita que el paciente porte tokens |
| 2 | `app/Http/Requests/Auth/PatientRedeemRequest.php` | Valida el formato del código recibido |
| 3 | `app/Http/Controllers/Api/V1/Auth/PatientAccessController.php` | Canjea el código por un token; revoca al hacer logout |
| 4 | `routes/api.php` | Rutas públicas y protegidas, separadas por tipo de portador |
| 5 | `app/Http/Middleware/EnsurePatientToken.php` y `EnsureStaffToken.php` | Guardias de segregación de identidad |
| 6 | `app/Models/MedicalSession.php` (método `closeSession`) | Revoca el token del paciente al cerrar la atención |

---

## 5. El flujo completo, en simple

```
Ana escribe su código "SV-754169" en la app
   → POST /api/v1/auth/patient/redeem { ctaCode: "SV-754169" }
   → el backend normaliza el código (mayúsculas, sin espacios)
   → busca una MedicalSession con ese cta_code, abierta
   → ¿no existe o está cerrada? → 422 INVALID_CODE (mensaje genérico, no revela por qué)
   → revoca cualquier token anterior de Ana (un dispositivo a la vez)
   → crea un token nuevo, grabado con la habilidad "session:{id}"
   → devuelve { token, tokenType: "Bearer", session: {...} }
```

Cuando el médico cierra la atención:

```
medicalSession->closeSession(...)
   → actualiza status, ended_at, closure_reason, summary, closed_by
   → $this->patient->tokens()->delete()
   → Ana pierde acceso automáticamente, sin lógica adicional
```

---

## 6. Verificación realizada

Se probó el ciclo completo en Tinker:

1. Se creó un token de prueba para un paciente con sesión abierta → confirmado `tokenable_type: "App\Models\Patient"`.
2. Se contaron los tokens del paciente → `1` (o más, si había restos de pruebas previas).
3. Se cerró la sesión con `closeSession('completed', 'resumen...', $user)`.
4. Se volvió a contar → **`0`**. El token murió junto con el cierre de la atención.

Durante la prueba se detectó (y corrigió) un error propio: `closure_reason` tiene un `CHECK constraint` en PostgreSQL que solo acepta `'completed'`, `'referred'` o `'abandoned'` — no el texto en español que ve el usuario final. Fue un recordatorio útil: el valor que se **muestra** en pantalla no siempre es el valor que se **almacena**.

---

## 7. Qué queda desbloqueado

Con este hito cerrado, ya es posible construir los endpoints exclusivos del paciente que vienen en los siguientes hitos de la Fase 5:

- `POST /consent-requests/{id}/approve` / `reject` / `revoke`
- `POST /messages/{id}/confirm`

Todos ellos usarán la misma comprobación `$request->user() instanceof Patient` que se construyó aquí, ya protegida por el middleware `patient.only`.

---

## 8. Resumen en una frase

**Antes:** el paciente no tenía ninguna forma de probar su identidad ante el backend.
**Ahora:** el paciente canjea su código de atención por una llave temporal que solo abre su propia conversación, y esa llave se destruye sola cuando la atención termina.
