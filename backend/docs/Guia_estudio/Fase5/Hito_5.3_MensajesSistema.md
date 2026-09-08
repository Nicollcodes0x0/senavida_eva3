# Hito 5.3 · Mensajes de sistema — pagando la deuda de Fase 4

**Fase:** 5 — Chat y Consentimientos
**Fecha:** 27 de agosto, 2026

---

## 1. ¿Qué problema resuelve este hito?

Desde el diseño original de la Fase 5 quedó declarada una deuda: los endpoints `PATCH /medical-sessions/{id}/stage` y `POST /medical-sessions/{id}/close` (construidos en Fase 4, Hito 3) debían insertar automáticamente un mensaje en el chat cada vez que la atención cambiara de etapa — pero en ese momento `ChatMessage` no existía todavía. Ahora que existe (Hito 5.2), corresponde volver atrás y completar esa promesa.

Sin esto, el paciente vería su conversación "congelada" mientras, en la trastienda, su atención avanza de Admisión a Categorización a Consulta Médica, sin ninguna señal de que algo está pasando.

---

## 2. Qué se construyó

Un único componente nuevo — `SystemMessageService` — enganchado en tres puntos ya existentes de `MedicalSessionController`:

```
advance()          → "La atencion avanzo a {etapa}."
advanceEmergency()  → "La atencion salto directamente a Consulta Medica por criterio de emergencia."
close()            → "La atencion fue cerrada."
```

---

## 3. Conceptos nuevos aprendidos

### 3.1 Service con método estático

```php
SystemMessageService::create($session, 'texto');
```

Se llama sin necesidad de instanciar el objeto primero (`new SystemMessageService()`) — útil cuando el servicio no necesita guardar ningún estado propio entre llamadas. Es el mismo patrón de "utilidad" que ya se usaba en `Response::allow()` dentro de las Policies.

### 3.2 Por qué un Service en vez de repetir el código

Se podría escribir `ChatMessage::create([...])` directamente dentro de cada método del controlador — funcionaría igual. Pero repetir esa misma lógica en tres lugares distintos significa que, si mañana cambia algo en cómo se arma un mensaje de sistema, hay que acordarse de tocar los tres. Centralizar esa lógica en un solo archivo evita ese riesgo.

Analogía: es como llamar a un repartidor en vez de que cada tienda tenga su propia flota de camiones. Si el repartidor cambia su forma de trabajar, ninguna tienda tiene que enterarse.

### 3.3 Reutilizar el `label()` del enum en vez de escribir texto duplicado

```php
SystemMessageService::create(
    $medicalSession,
    "La atencion avanzo a {$siguienteEtapa->label()}."
);
```

En vez de escribir "La atención avanzó a Categorización" y "La atención avanzó a Consulta Médica" como dos strings distintos y separados, se reutiliza el método `label()` que ya existía en `MedicalSessionStatus` (construido en Fase 4) — el texto legible de cada etapa vive en un solo lugar, y el mensaje de sistema simplemente lo consulta.

### 3.4 El motivo clínico de una emergencia no se expone en el chat del paciente

Se decidió explícitamente que el mensaje de sistema del salto de emergencia sea neutro ("por criterio de emergencia"), sin repetir el motivo médico interno (`triage_skip_reason`, por ejemplo "sospecha de infarto agudo"). Ese detalle queda reservado al registro clínico interno, visible para el equipo de salud — no es información que el paciente necesite leer en su propia conversación.

---

## 4. Archivo construido

| Archivo | Responsabilidad |
|---|---|
| `app/Services/SystemMessageService.php` | Crea un `ChatMessage` con `sender_type: 'system'`, sin que ningún humano lo dispare directamente |
| `app/Http/Controllers/Api/V1/MedicalSessionController.php` *(modificado)* | Se agregó una llamada a `SystemMessageService::create()` en `advance()`, `advanceEmergency()` y `close()` |

---

## 5. Verificación realizada (con evidencia HTTP real)

Se probó el flujo completo en Swagger sobre una sesión ya usada en el Hito 5.2 (con mensajes previos de un médico y una paciente):

1. `PATCH /medical-sessions/{id}/stage` con un usuario de admisión → `200`, la sesión avanzó de `in_admission` a `in_triage`.
2. `GET /medical-sessions/{id}/messages` sobre la misma sesión → el mensaje de sistema apareció **automáticamente**, al final del historial cronológico, mezclado con los mensajes humanos anteriores:

```json
{
  "senderType": "system",
  "senderId": null,
  "senderName": "Sistema",
  "messageType": "system",
  "body": "La atencion avanzo a Categorización.",
  "origin": "system",
  "confirmedByPatientAt": null
}
```

Nadie escribió ese mensaje manualmente — se generó solo, como efecto secundario de la llamada a `PATCH /stage`.

---

## 6. Qué queda pendiente para hitos futuros

- **Hito 5.4:** el sistema de consentimientos (`Consent`), la segunda gran pieza de la Fase 5.
- **Hito 5.5:** la cascada completa de cierre — revocar consentimientos, expirar el CTA y generar el mensaje de sistema, todo dentro de una única transacción.

---

## 7. Resumen en una frase

**Antes:** la conversación del paciente no reflejaba en absoluto los avances de su propia atención — un vacío heredado de cuando el chat todavía no existía.
**Ahora:** cada cambio de etapa y cada cierre de atención dejan un rastro automático y verificado en el chat, sin que nadie tenga que escribirlo a mano.
