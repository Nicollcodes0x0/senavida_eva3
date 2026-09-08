# Hito 5.4 · `Consent` — el sistema de consentimientos

**Fase:** 5 — Chat y Consentimientos
**Fecha:** 27 de agosto, 2026

---

## 1. ¿Qué problema resuelve este hito?

Que el médico pueda **solicitar** un permiso al paciente (por ejemplo, "compartir tus datos con tu hijo Pedro"), y que **solo el paciente** pueda aprobar, rechazar o revocar ese permiso — nunca quien lo solicitó, ni siquiera un `super_admin`. En un contexto de urgencias con un paciente sordo, el consentimiento formal y registrado es la manera en que el sistema protege su autonomía, la misma que ya se protegió en Fase 4 al impedir que cualquier rol administrativo edite la ficha clínica de un paciente.

---

## 2. Qué se construyó

Cinco endpoints sobre una única tabla `consents`, con una máquina de estados:

```
pending ──approve──> granted ──revoke──> revoked
   │
   └──reject──> rejected
```

| # | Método | Ruta | Quién |
|---|---|---|---|
| K1 | GET | `/medical-sessions/{id}/consents` | Staff de la unidad, paciente dueño |
| K2 | POST | `/medical-sessions/{id}/consent-requests` | Solo médico |
| K3 | POST | `/consent-requests/{id}/approve` | Solo el paciente |
| K4 | POST | `/consent-requests/{id}/reject` | Solo el paciente |
| K5 | POST | `/consent-requests/{id}/revoke` | Solo el paciente |

---

## 3. Conceptos nuevos aprendidos

### 3.1 Plantillas en el enum, no texto libre en la base de datos (D-08)

```php
enum ConsentType: string
{
    case ShareWithContacts = 'share_with_contacts';
    // ...

    public function title(): string { /* ... */ }
    public function description(?string $contactName = null): string { /* ... */ }
}
```

El título y la descripción de un consentimiento **no los escribe el médico** — se generan desde el enum al momento de servir la respuesta. Es una decisión de seguridad, no de estilo: si el título fuera texto libre, un error de tipeo con dos contactos de apellido parecido podría autorizar el envío de datos clínicos a la persona equivocada. Al guardar `patient_contact_id` como FK real y generar el texto desde una plantilla fija, ese riesgo desaparece.

### 3.2 Máquina de estados con reglas de transición en el modelo, no en el controlador

```php
public function revoke(array $evidence): void
{
    if (! $this->status->puedeRevocarse()) {
        throw new ApiException('INVALID_CONSENT_TRANSITION', '...', 409);
    }
    // ...
}
```

Cada método de transición (`approve`, `reject`, `revoke`) valida su propio estado de origen **dentro del modelo**, no en el controlador. Esto garantiza que, sin importar desde dónde se llame, la máquina de estados nunca pueda saltarse un paso — por ejemplo, revocar algo que nunca se otorgó.

### 3.3 Policy con reglas distintas según el rol exacto, no solo el tipo de usuario

```php
public function create(User $user, MedicalSession $medicalSession): Response
{
    if ($user->role !== 'medico') {
        return Response::deny('FORBIDDEN_ROLE|...');
    }
    // ...
}
```

A diferencia de `ChatMessagePolicy` (Hito 5.2), donde cualquier miembro del staff de la unidad podía crear un mensaje, aquí **solo el rol `medico`** puede solicitar un consentimiento — ni admisión ni categorización. Es un ejemplo de cómo la misma estructura de Policy (staff vs. paciente) puede además discriminar por rol específico cuando el negocio lo exige.

### 3.4 Validación de pertenencia cruzada en el controlador

```php
if ($contacto === null || $contacto->patient_id !== $medicalSession->patient_id) {
    throw new ApiException('FORBIDDEN_CONTACT', '...', 422);
}
```

No basta con que el `contactId` sea un UUID válido que exista en la base de datos (eso ya lo valida el `FormRequest` con `Rule::exists`) — también debe pertenecer **al paciente de esa atención específica**. Sin esta segunda verificación, un médico podría (por error o mala intención) pasar el UUID del contacto de un paciente distinto.

---

## 4. Archivos construidos

| # | Archivo | Responsabilidad |
|---|---|---|
| 1 | `app/Enums/ConsentType.php` | 11 tipos documentados, 4 con plantilla y validación real |
| 2 | `app/Enums/ConsentStatus.php` | 4 estados + reglas de transición válida |
| 3 | `database/migrations/..._create_consents_table.php` | Tabla única, `patient_contact_id` FK nullable, `evidence` JSONB |
| 4 | `app/Models/Consent.php` | Relaciones + los 3 métodos de transición con sus guardas |
| 5 | `app/Policies/ConsentPolicy.php` | Ver (staff+paciente) / solicitar (solo médico) / responder (solo paciente) |
| 6 | `app/Http/Requests/StoreConsentRequestRequest.php` | Valida solo los 4 tipos implementados; `contactId` condicional |
| 7 | `app/Http/Resources/ConsentResource.php` | Arma `title`/`description` desde plantilla, nunca desde la BD |
| 8 | `app/Http/Controllers/Api/V1/ConsentController.php` | Los 5 endpoints + verificación de pertenencia del contacto + evidencia |
| — | `routes/api.php` | `GET`/`store` accesibles según corresponde; `approve`/`reject`/`revoke` exclusivos de `patient.only` |

---

## 5. Decisiones ratificadas

- **Una sola tabla (`consents`), no dos** — "solicitud" es el estado inicial de un consentimiento, no una entidad distinta.
- **11 valores en el enum, 4 validados** — mismo criterio que `MessageType`/`MessageOrigin` en el Hito 5.2: se documenta el alcance completo del contrato, pero solo se acepta lo que el sistema realmente puede producir hoy.
- **`revoke` solo desde `granted`** — revocar significa retirar un permiso que se había dado; no aplica sobre algo que nunca se otorgó.

---

## 6. Verificación realizada (con evidencia HTTP real)

Se probó el ciclo completo en Swagger, con dos actores reales de la misma atención:

| # | Prueba | Resultado |
|---|---|---|
| 1 | Médico solicita `start_care` | `201` — `title`/`description` generados desde plantilla |
| 2 | El mismo médico intenta aprobar su propia solicitud | `403 WRONG_TOKEN_TYPE` |
| 3 | La paciente aprueba | `200` — `status: "granted"`, `grantedAt` con fecha |
| 4 | La paciente revoca | `200` — `status: "revoked"`, `revokedAt` con fecha |

La prueba 2 es la evidencia central del hito: confirma en ejecución, no solo en el diseño, que la regla de autonomía del paciente se cumple incluso cuando quien intenta saltársela es la misma persona que generó la solicitud.

Durante las pruebas se detectó un error de transcripción manual de un UUID (`404` en vez del `403` esperado), resuelto verificando el registro real en la base de datos antes de repetir la prueba — un recordatorio de que un error humano de copiado no debe confundirse con un fallo del sistema.

---

## 7. Qué queda pendiente

**Hito 5.5**, el último de la Fase 5: la cascada completa de cierre de una atención — revocar automáticamente todos los consentimientos vigentes, expirar el CTA, y generar el mensaje de sistema correspondiente, todo dentro de una única transacción.

---

## 8. Resumen en una frase

**Antes:** no existía ningún mecanismo formal para que un paciente autorizara o rechazara acciones sobre su propia atención.
**Ahora:** el paciente decide, con evidencia registrada de cada decisión, y ni el médico que solicita ni ningún rol administrativo puede responder en su nombre — verificado con una prueba real donde el propio solicitante intentó y fue bloqueado.
