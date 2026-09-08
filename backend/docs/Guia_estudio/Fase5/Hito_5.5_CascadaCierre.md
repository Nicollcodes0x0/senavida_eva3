# Hito 5.5 · Cascada de cierre completa

**Fase:** 5 — Chat y Consentimientos (hito de cierre)
**Fecha:** 27 de agosto, 2026

---

## 1. ¿Qué problema resuelve este hito?

Cerrar una atención debía ser **una sola operación atómica**, pero hasta antes de este hito era una serie de llamadas sueltas: `closeSession()` actualizaba el estado y revocaba el token del paciente, y por separado el controlador llamaba a `SystemMessageService::create()`. Además, faltaban dos efectos que el propio diseño de la fase exigía: revocar los consentimientos que seguían vigentes y expirar el CTA asociado.

Sin esto, quedaba una inconsistencia real: una atención podía cerrarse dejando "vivo" un permiso para compartir datos clínicos con un contacto — el paciente cerró su capítulo de esa urgencia, pero el consentimiento seguiría técnicamente vigente para siempre.

---

## 2. Qué se construyó

No se agregó ninguna entidad nueva. Este hito fue una **modificación quirúrgica** del método `closeSession()` en el modelo `MedicalSession`, envolviendo cinco efectos dentro de una sola transacción:

```php
public function closeSession(string $closureReason, string $summary, User $closedBy): void
{
    DB::transaction(function () use ($closureReason, $summary, $closedBy) {
        $this->update([...]);                          // 1. actualiza el estado
        $this->patient->tokens()->delete();             // 2. revoca acceso del paciente
        $this->consents()...->each(...);                // 3. revoca consentimientos vigentes
        $this->temporaryAccessCode?->update([...]);      // 4. expira el CTA
        SystemMessageService::create($this, '...');       // 5. deja constancia en el chat
    });
}
```

---

## 3. Conceptos nuevos aprendidos

### 3.1 Transacción con múltiples efectos de naturaleza distinta

Ya se había usado `DB::transaction()` en Fase 4 (crear la sesión + consumir el CTA). Aquí el mismo mecanismo, pero con cinco pasos que tocan **tres tablas distintas** (`medical_sessions`, `consents`, `temporary_access_codes`) más la creación de un `ChatMessage`. Si cualquiera de los cinco falla, PostgreSQL deshace todos — no queda ningún efecto a medias.

### 3.2 Reutilizar un valor de enum ya existente en otro módulo

Para "expirar" el CTA no hizo falta agregar ningún valor nuevo: `'expired'` ya existía en `temporary_access_codes.status` desde el Hito 2 de Fase 4 (se usa cuando un paciente genera un código nuevo y el anterior queda invalidado). Antes de escribir código, se verificó esto contra el contrato real del proyecto — evitando crear una segunda forma de decir lo mismo.

### 3.3 Evidencia distinta según quién causa la acción

```php
'evidence' => ['reason' => 'session_closed'],
```

Cuando el paciente revoca un consentimiento activamente (Hito 5.4), la evidencia guarda IP y user-agent reales — es una decisión humana en el momento. Cuando el **sistema** revoca un consentimiento como efecto del cierre de la atención, no hay una decisión humana que registrar — por eso la evidencia es distinta: un motivo estructurado (`session_closed`), no datos de sesión HTTP que no existen en ese contexto.

---

## 4. Verificación realizada (con evidencia real de la transacción completa)

Se preparó un consentimiento en estado `granted` sobre una sesión ya usada en hitos anteriores, se cerró la atención vía `POST /medical-sessions/{id}/close`, y se comprobaron los tres efectos nuevos directamente en la base de datos:

| Efecto verificado | Resultado |
|---|---|
| Consentimiento revocado | `status: "revoked"`, `evidence: {"reason": "session_closed"}` |
| CTA expirado | `status: "expired"` |
| Mensaje de sistema de cierre | `"La atencion fue cerrada."`, `sender_type: "system"` |

El dato más significativo: los tres timestamps (`revoked_at` del consentimiento, `ended_at` de la sesión, y `sent_at` del mensaje) resultaron **exactamente el mismo instante** — confirmando que los tres efectos ocurrieron dentro de la misma transacción atómica, no como pasos secuenciales independientes.

---

## 5. Resumen en una frase

**Antes:** cerrar una atención dejaba efectos de seguridad sueltos e incompletos — consentimientos que seguían vigentes, códigos que nunca expiraban formalmente.
**Ahora:** cerrar una atención es una única operación de todo-o-nada, verificada con evidencia real de que los cinco efectos ocurren juntos, en el mismo instante.

---

## 6. Cierre de la Fase 5

Con este hito, la Fase 5 — Chat y Consentimientos queda **completa**:

| Hito | Contenido |
|---|---|
| 5.0 | Acceso del paciente — token derivado del CTA |
| 5.1 | Catálogo de Pictogramas |
| 5.2 | `ChatMessage` — chat con derivación de identidad desde el backend |
| 5.3 | Mensajes de sistema automáticos |
| 5.4 | `Consent` — consentimientos con máquina de estados y autonomía del paciente |
| 5.5 | Cascada de cierre completa — este hito |

Los cinco hitos están construidos, probados con evidencia HTTP real, y documentados.
