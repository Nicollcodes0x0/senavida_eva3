# Hito 5.1 · Catálogo de Pictogramas

**Fase:** 5 — Chat y Consentimientos
**Fecha:** 26 de agosto, 2026
**Ratifica:** D-B del diseño de Fase 5 (dependencia de `ChatMessage.pictogram_id`)

---

## 1. ¿Qué problema resuelve este hito?

`ChatMessage` (próximo hito) necesita una columna `pictogram_id` que sea una **referencia real** a un catálogo existente — no una columna huérfana sin FK. Además, el prototipo original tenía dos bugs de diseño que el contrato señala explícitamente como defectos a corregir:

1. **El emoji vivía en un `switch` de código** (`getPictogramEmoji()`), importado en 4 componentes del frontend. Cualquier pictograma nuevo creado desde administración aparecía con un símbolo genérico (🏥), porque el código no sabía de su existencia.
2. **El color se guardaba como clases de Tailwind crudas** (`colorClass: 'border-brand-coral bg-brand-coral-light/20...'`) directamente en la base de datos — acoplamiento fuerte entre datos y presentación, con riesgo real de que Tailwind 4 (JIT) no compilara clases generadas dinámicamente.

Este hito construye el catálogo correctamente desde el inicio, resolviendo ambos problemas de raíz.

---

## 2. Qué se construyó

Dos tablas relacionadas:

```
PictogramCategory (solo lectura en esta fase)
    │ hasMany
    ▼
Pictogram (CRUD completo, exclusivo de admin_institucional)
```

**`PictogramCategory`**: `id`, `name`, `sort_order`.

**`Pictogram`**: `id`, `pictogram_category_id` (FK), `title`, `phrase`, `speech_text`, `emoji`, `severity` (enum), `is_active`, `sort_order`.

---

## 3. Conceptos nuevos aprendidos

### 3.1 Token semántico en vez de estilo crudo

En vez de guardar `colorClass` con clases de Tailwind, se guarda `severity` (`critical`, `warning`, `info`, `neutral`) — un **significado**, no una **apariencia**. El frontend decide cómo pintar cada severidad; el backend solo comunica el dato clínico. Esto desacopla el backend de cualquier framework de estilos que el frontend decida usar, hoy o en el futuro.

### 3.2 Enum de PHP como fuente de verdad para validación

```php
enum PictogramSeverity: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Info = 'info';
    case Neutral = 'neutral';
}
```

Y en el FormRequest:
```php
'severity' => ['required', new Enum(PictogramSeverity::class)],
```

Un solo lugar define los valores válidos. Si se agrega un quinto valor al enum, la validación se actualiza sola — no hay que mantener la lista en dos sitios distintos.

### 3.3 `foreignUuid()->constrained()` con protección `restrict`

```php
$table->foreignUuid('pictogram_category_id')->constrained('pictogram_categories');
```

Por defecto, PostgreSQL **impide borrar una categoría** si todavía tiene pictogramas apuntando a ella (`onDelete('restrict')`). Es la protección correcta cuando no hay soft delete ni cascada: un intento de borrado peligroso falla de forma segura, en vez de arrastrar borrados en cadena o dejar registros huérfanos.

### 3.4 Policy con lectura abierta, escritura restringida

```php
public function viewAny($user): bool
{
    return true; // sin type-hint: acepta tanto User como Patient
}

public function create(User $user): Response
{
    return $user->role === 'admin_institucional'
        ? Response::allow()
        : Response::deny('FORBIDDEN_ROLE|...');
}
```

`viewAny` **no tiene type-hint** en su parámetro deliberadamente: si se escribiera `User $user`, PHP lanzaría un `TypeError` en cuanto un `Patient` (que también necesita leer el catálogo, según §7.9 del contrato) intentara acceder. `create`/`update` sí mantienen el type-hint estricto, porque nunca deben ser alcanzables por un paciente.

### 3.5 Separación de responsabilidades entre roles de administración

El contrato distingue dos tipos de administración, cada una con su propio dueño:

| Tipo | Rol dueño | Ejemplos |
|---|---|---|
| Estructura del sistema (multi-tenant) | `super_admin` | Crear organizaciones, centros, unidades |
| Operación clínica de un centro | `admin_institucional` | Gestionar pictogramas, seguridad TI, auditoría |

`super_admin` es un rol "libre" sin centro ni unidad asociados — gestiona la **existencia** de los centros, no las decisiones operativas de cada uno. Por eso, aunque parezca contraintuitivo, `super_admin` **no puede** crear pictogramas — es una decisión de diseño confirmada explícitamente en la matriz de capacidades del contrato (§9.1), no un descuido. Se verificó en la práctica: incluso con el token de Super Admin, `POST /pictograms` devuelve `403 FORBIDDEN_ROLE`.

### 3.6 Seeders para datos reproducibles

Se creó `PictogramSeeder` con 4 categorías y 9 pictogramas reales, permitiendo recrear el catálogo de prueba con un solo comando (`php artisan db:seed --class=PictogramSeeder`) en cualquier instalación nueva del proyecto.

---

## 4. Archivos construidos

| # | Archivo | Responsabilidad |
|---|---|---|
| 1 | `database/migrations/..._create_pictogram_categories_table.php` | Tabla de categorías |
| 2 | `database/migrations/..._create_pictograms_table.php` | Tabla de pictogramas, con FK protegida |
| 3 | `app/Enums/PictogramSeverity.php` | 4 valores de severidad |
| 4 | `app/Models/PictogramCategory.php` | Modelo + relación `hasMany` |
| 5 | `app/Models/Pictogram.php` | Modelo + relación `belongsTo` + cast de enum |
| 6 | `app/Http/Requests/StorePictogramRequest.php` | Validación + traducción camelCase → snake_case |
| 7 | `app/Policies/PictogramPolicy.php` | Lectura abierta, escritura exclusiva de `admin_institucional` |
| 8 | `app/Http/Controllers/Api/V1/PictogramCategoryController.php` | Endpoint de solo lectura |
| 9 | `app/Http/Controllers/Api/V1/PictogramController.php` | CRUD (sin `delete`, según lo ratificado) |
| 10 | `database/seeders/PictogramSeeder.php` | Datos reales de prueba |
| — | `routes/api.php` | Rutas: `GET` accesible a staff+paciente, `POST`/`PATCH` exclusivo de staff |

---

## 5. Decisiones ratificadas

- **`PictogramCategory` solo lectura en esta fase** — el CRUD completo se difiere a Fase 6 (Administración), donde el contrato ya agrupa este trabajo (M18+M19+M12). Evita construir de más antes de necesitarlo.
- **Sin soft delete en `Pictogram`** — el contrato solo exige poder activar/desactivar (`is_active`), nunca eliminar. Un pictograma desactivado ya cumple la regla de "desaparece del portal del paciente" (§9.9).
- **`GET` de ambos recursos accesible tanto a staff como a paciente** — confirmado en el contrato §7.9: el paciente necesita este catálogo para armar sus propios mensajes.

---

## 6. Verificación realizada (con evidencia HTTP real vía Swagger)

Se probaron los 4 endpoints en `/api/documentation`, con dos tokens reales distintos:

| Prueba | Token usado | Resultado | Confirma |
|---|---|---|---|
| `GET /pictogram-categories` | Super Admin | `200` — 4 categorías | Lectura abierta a cualquier staff |
| `GET /pictograms` | Super Admin | `200` — 9 pictogramas, orden correcto | Filtro y orden por `categoryId,sortOrder` |
| `POST /pictograms` | Super Admin | `403 FORBIDDEN_ROLE` | La Policy bloquea correctamente incluso al rol más alto |
| `POST /pictograms` | Admin Institucional | `201 Created` | El rol correcto sí puede crear |

El caso más valioso es el `403`: confirma en la práctica, no solo en el diseño, que la separación de responsabilidades entre `super_admin` y `admin_institucional` se respeta a nivel de código.

Tras las pruebas, se limpiaron los datos y tokens de prueba, dejando el catálogo en su estado real de 9 pictogramas.

---

## 7. Qué queda desbloqueado

Con este hito cerrado, `ChatMessage.pictogram_id` (Hito 5.2) ya puede ser una FK real hacia `pictograms`, permitiendo construir mensajes de tipo `pictogram` con datos reales — emoji, frase y texto de voz — en vez de placeholders.

---

## 8. Resumen en una frase

**Antes:** no existía ningún catálogo de pictogramas en el backend; el emoji y el color vivían acoplados al código del frontend.
**Ahora:** existe un catálogo real con datos semánticos, protegido por una Policy que respeta la separación de responsabilidades del contrato, y verificado con pruebas HTTP reales.
