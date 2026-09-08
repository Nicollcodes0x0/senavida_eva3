# 📘 Guía de estudio · Hito 3 — Sesión Médica

## 🎯 Objetivo del hito

Construir `MedicalSession`: la entidad central del sistema, de la que cuelga todo lo clínico. Con ella se cierra el pendiente T2 del Hito 2 y se abre formalmente la atención de un paciente.

---

## 🧩 Conceptos nuevos aprendidos

### 1. Enum nativo de PHP
Una lista cerrada de valores posibles, con métodos propios. Resolvió D-07: un solo campo `status` sirve tanto para guardar el dato como para derivar su etiqueta en español (`label()`), sin duplicar información.

```php
enum MedicalSessionStatus: string {
    case InTriage = 'in_triage';
    public function label(): string {
        return match($this) { self::InTriage => 'Categorización', ... };
    }
}
```

### 2. Índice único parcial (PostgreSQL)
`UNIQUE ... WHERE condición` — una restricción de unicidad que solo aplica a las filas que cumplen esa condición. Permitió la regla "un paciente, una atención **abierta**" sin bloquear sus visitas futuras.

### 3. UUID ≠ ULID
Se parecen a simple vista, pero PostgreSQL los tipa distinto. Una migración falló por mezclar los dos en una llave foránea. Lección: verificar siempre qué usa el resto del proyecto antes de asumir.

### 4. `$fillable` es una lista blanca
Eloquent descarta en silencio cualquier campo no declarado en `$fillable` al hacer `create()`. Un campo nuevo (`cta_code`) se agregó a la migración pero se olvidó en el modelo — el dato se perdía sin ningún error visible.

### 5. `Response::deny('mensaje')` en vez de `false`
Una Policy puede explicar **por qué** rechaza, no solo que rechaza. Se usó además un truco simple (`"CODIGO|mensaje"`) para transportar un código de error junto al texto, sin crear una clase nueva.

### 6. Middleware personalizado
Una capa que intercepta la petición **antes** del controlador. `EnsureMedicalSessionIsActive` bloquea cualquier escritura sobre una atención cerrada, reutilizable para todos los módulos clínicos futuros.

### 7. Laravel convierte excepciones en el camino
`AuthorizationException → AccessDeniedHttpException` y `ModelNotFoundException → NotFoundHttpException`. Hay que capturar la clase **convertida**, no la original — y la regla específica siempre va antes que la genérica.

---

## 🏗️ Lo que se construyó

| Pieza | Archivo |
|---|---|
| Migración de la tabla | `create_medical_sessions_table.php` |
| Migraciones de ajuste | `consumed` en CTA, `cta_code`, `triage_skip_*` |
| Enum de estado | `app/Enums/MedicalSessionStatus.php` |
| Modelo | `app/Models/MedicalSession.php` |
| Policy | `app/Policies/MedicalSessionPolicy.php` |
| Excepción propia | `app/Exceptions/ApiException.php` |
| Middleware | `app/Http/Middleware/EnsureMedicalSessionIsActive.php` |
| Controlador (S1-S5) | `app/Http/Controllers/Api/V1/MedicalSessionController.php` |
| Handler global de errores | `bootstrap/app.php` |

---

## 🔑 Decisiones de diseño resueltas

- **D-07**: un solo `status`, la etiqueta se deriva, nunca se guarda.
- **T2 → disuelto en S1**: consumir el CTA es un efecto interno de abrir la sesión.
- **D-23**: salto de emergencia permitido, solo `medico`, con motivo obligatorio (30+ caracteres) y auditoría permanente.
- **D-16 ratificado**: solo `medico` cierra (no `categorizacion`).

---

## 🐞 Tres bugs encontrados y corregidos

1. **`close()` no validaba la etapa** — se podía cerrar una atención que nunca pasó por consulta médica. Corregido agregando la condición de `status`.
2. **`super_admin` veía fichas de pacientes** — contradecía la tabla de segregación de datos por rol. Corregido en `PatientPolicy`.
3. **El `code` no se separaba del `message`** — porque Laravel convierte `AuthorizationException` en `AccessDeniedHttpException` antes de que el handler la vea. Corregido capturando la clase convertida.

Ninguno de los tres estaba en el plan original. Los tres salieron de probar casos fuera del camino feliz.

---

## 📊 Estado del proyecto tras este hito

| Fase | Estado |
|:---:|:---:|
| **Fase 4** — Paciente, CTA y Sesión Médica | ✅ **Completa** (Hitos 0, A-E, 1, 2, 3, 4) |
| **Fase 5** — Chat y Consentimientos | ⏭️ Siguiente |

---

## ▶️ Lo que viene

**Fase 5**, que el propio contrato describe como *"el núcleo del producto"*: el Chat entre paciente y personal, los Consentimientos, y el Broadcasting en tiempo real para que el chat funcione sin recargar la página.

---

*Guía de estudio · Proyecto SeñaVida · Fase 4 · Hito 3*
