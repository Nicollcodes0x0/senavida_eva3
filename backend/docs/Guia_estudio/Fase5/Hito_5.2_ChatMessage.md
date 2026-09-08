# Hito 5.2 · `ChatMessage` — el chat de la atención

**Fase:** 5 — Chat y Consentimientos
**Fecha:** 26 de agosto, 2026

---

## 1. ¿Qué problema resuelve este hito?

Es la funcionalidad central del producto: el canal de mensajes entre paciente y personal de salud durante la atención. Además, cierra una **vulnerabilidad de seguridad real** detectada en el análisis del prototipo original: el frontend armaba el mensaje completo —incluyendo quién lo enviaba— directamente en el navegador. Cualquiera con acceso a las herramientas de desarrollo del navegador podía modificar ese código y **hacerse pasar por otra persona** (por ejemplo, un paciente enviando un mensaje que aparentara venir del médico).

Este hito traslada esa decisión al backend, donde no se puede falsificar: el servidor mira quién está autenticado (`User` o `Patient`) y calcula él mismo quién es el emisor — el cliente nunca puede escribir esa información.

---

## 2. Qué se construyó

Cuatro endpoints:

| # | Método | Ruta | Quién |
|---|---|---|---|
| CH1 | GET | `/medical-sessions/{id}/messages` | Staff de la unidad, paciente dueño |
| CH2 | POST | `/medical-sessions/{id}/messages` | Staff de la unidad, paciente dueño |
| CH3 | POST | `/messages/{id}/confirm` | Solo el paciente |
| CH4 | POST | `/messages/{id}/read` | Solo el staff |

---

## 3. Conceptos nuevos aprendidos

### 3.1 Derivar identidad del emisor, nunca confiar en el cliente

El corazón del hito. En vez de leer `senderType` del body del request, el controlador pregunta: *"¿quién está autenticado ahora mismo?"*

```php
if ($user instanceof Patient) {
    $senderType = 'patient';
    $senderId   = null; // el contrato exige NULL cuando el emisor es el paciente
    $senderName = $user->name;
    $origin     = MessageOrigin::Patient;
} else {
    $senderType = 'staff';
    $senderId   = $user->id;
    $origin     = match ($user->role) {
        'admision'       => MessageOrigin::Admission,
        'categorizacion' => MessageOrigin::Triage,
        'medico'         => MessageOrigin::Doctor,
    };
}
```

Analogía: como un sobre de correo certificado — el remitente lo estampa la oficina de correos según quién lo entregó en el mostrador, no lo escribe a mano el propio remitente.

Verificado con evidencia HTTP real: se envió el mismo tipo de petición desde una cuenta de médico y desde una cuenta de paciente, **sin que el cliente enviara nunca** `senderType`, `senderId`, `senderName` ni `origin` — y ambas respuestas mostraron los valores correctos y distintos según quién estaba autenticado.

### 3.2 `senderName` como copia (snapshot), no como relación en vivo

Se guarda el nombre del emisor directamente en la fila del mensaje, en vez de solo guardar `sender_id` y consultar el nombre actual del usuario cada vez. Si el médico cambia de nombre o se desactiva su cuenta meses después, el mensaje histórico de esa atención debe seguir mostrando quién lo escribió *en ese momento* — es un registro clínico-legal, no un dato que deba actualizarse retroactivamente.

### 3.3 Paginación por cursor, no por número de página

```php
ChatMessage::where('medical_session_id', $id)
    ->orderBy('sent_at')
    ->cursorPaginate(50);
```

En un chat que crece mientras se está leyendo, el offset numérico (`page=1,2,3`) se desordena: si llega un mensaje nuevo justo cuando se pide la "página 2", se puede ver un mensaje repetido o saltarse uno. El cursor apunta a una posición fija ("después de este mensaje exacto"), inmune a inserciones nuevas.

### 3.4 Validación condicional con `Rule::requiredIf()`

```php
'pictogramId' => [
    Rule::requiredIf(fn () => $this->input('messageType') === 'pictogram'),
    'nullable',
    ...
],
```

`pictogramId` es obligatorio solo si `messageType` es `'pictogram'` — en cualquier otro caso, es opcional. Es el mismo patrón de un formulario que pide un dato extra solo si se marcó cierta opción.

### 3.5 Policy con reglas distintas para `User` y `Patient`, sobre la misma acción

```php
public function view($user, MedicalSession $medicalSession): Response
{
    if ($user instanceof Patient) {
        return $user->id === $medicalSession->patient_id
            ? Response::allow()
            : Response::deny('FORBIDDEN_PATIENT|...');
    }

    return $user->unit_id === $medicalSession->unit_id
        ? Response::allow()
        : Response::deny('FORBIDDEN_UNIT|...');
}
```

Y dos métodos con type-hint estricto para acciones exclusivas de un solo tipo de usuario: `confirm(Patient $patient, ...)` solo acepta pacientes, `markAsRead(User $user, ...)` solo acepta staff — si el tipo equivocado intentara llegar, PHP lanzaría un error antes de que la lógica se ejecute.

---

## 4. Archivos construidos

| # | Archivo | Responsabilidad |
|---|---|---|
| 1 | `app/Enums/MessageType.php`, `MessageOrigin.php`, `MessageStatus.php` | Los 3 enums del mensaje |
| 2 | `database/migrations/..._create_chat_messages_table.php` | Tabla, con `deleted_at` incluido desde el inicio |
| 3 | `app/Models/ChatMessage.php` | Modelo, relaciones, `SoftDeletes` |
| 4 | `app/Policies/ChatMessagePolicy.php` | Reglas por tipo de usuario y acción |
| 5 | `app/Http/Requests/StoreChatMessageRequest.php` | Validación, sin los 4 campos derivados |
| 6 | `app/Http/Resources/ChatMessageResource.php` | Serialización, con `pictogram` anidado |
| 7 | `app/Http/Controllers/Api/V1/ChatMessageController.php` | Los 4 endpoints, con la derivación de identidad |
| — | `routes/api.php` | Registro de las 4 rutas |
| — | `app/Http/Controllers/Api/V1/Auth/PatientAccessController.php` | Deuda del Hito 5.0: se agregaron sus anotaciones Swagger faltantes |

---

## 5. Decisiones ratificadas

- **`deleted_at` incluido desde el inicio** — aunque no existe todavía ningún endpoint de borrado, el propio contrato lo contempla en el schema de referencia. Agregar la columna ahora, sobre una tabla vacía, es gratis; agregarla después sobre conversaciones reales sería más delicado.
- **Alcance reducido en los tres enums** — se documentan solo los valores que el sistema puede producir hoy (`text`, `quick_message`, `pictogram`, `system` para el tipo; `sent`, `read` para el estado). Valores como `gesture_prediction`, `delivered` o el origen `interpreter` existen en el contrato completo pero no tienen ningún productor real todavía — se dejan fuera del enum para no prometer en Swagger algo que el sistema no hace.
- **`Rule::notIn(['system'])` en `messageType`** — ningún cliente (médico, paciente) puede enviar manualmente un mensaje de tipo `system`; esos los generará el propio backend automáticamente en el Hito 5.3.

---

## 6. Verificación realizada (con evidencia HTTP real)

Se probó el ciclo completo en Swagger, con tres actores reales de una misma atención:

| Prueba | Actor | Resultado | Confirma |
|---|---|---|---|
| `POST .../messages` | Médico (Dr. Uno SR) | `201` — `senderType: "staff"`, `senderId` real, `origin: "doctor"` | Derivación correcta para staff |
| `POST /auth/patient/redeem` | Paciente | `200` — token acotado a la sesión | El flujo del Hito 5.0 interopera con el chat |
| `POST .../messages` | Paciente (Prueba Dos) | `201` — `senderType: "patient"`, `senderId: null`, auto-confirmado | Derivación correcta para paciente, y regla de auto-confirmación |
| `POST /messages/{id}/read` | Médico | `200` — `status: "read"` | El staff puede marcar como leído |

En ninguna de las peticiones de creación se envió `senderType`, `senderId`, `senderName` ni `origin` — y aun así, cada respuesta mostró los valores correctos según quién estaba autenticado en cada caso.

---

## 7. Qué queda pendiente para hitos futuros

- **Hito 5.3:** los endpoints `PATCH .../stage` y `POST .../close` de Fase 4 deben empezar a insertar automáticamente un mensaje de sistema (`messageType: "system"`) cada vez que la atención cambia de etapa — deuda declarada desde el diseño de la Fase 5.
- **Hito 5.4:** el sistema de consentimientos (`Consent`), que usará el mismo patrón de Policy con reglas distintas para `User` y `Patient`.

---

## 8. Resumen en una frase

**Antes:** el chat no existía, y el diseño original dejaba que el propio cliente declarara quién enviaba cada mensaje — una vulnerabilidad de suplantación de identidad.
**Ahora:** el chat existe, y es el backend —nunca el cliente— quien decide y registra quién envió cada mensaje, verificado con pruebas HTTP reales entre un médico y una paciente.
