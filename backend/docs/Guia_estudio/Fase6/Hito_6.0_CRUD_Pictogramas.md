# Guía de Estudio — Hito 6.0: CRUD completo de Pictogramas

## 1. Objetivo del hito

Convertir el catálogo de pictogramas, que desde la Fase 5 era **solo lectura**, en un mantenedor completo: crear, actualizar parcialmente, desactivar y reactivar pictogramas y sus categorías — sin tocar la base de datos a mano.

## 2. ¿Por qué era necesario?

El Hito 5.1 creó el catálogo únicamente para que el chat tuviera una tabla real a la cual apuntar. El contrato del proyecto exige explícitamente que `admin_institucional` pueda gestionar ese catálogo, pero hasta este hito solo existían `GET` y un `POST` a medio documentar. Sin este trabajo, cualquier pictograma nuevo requería un `INSERT` manual en PostgreSQL.

## 3. Conceptos clave aprendidos

### 3.1 PATCH no es PUT — y las reglas de validación deben reflejarlo

**El problema real que encontramos:** el endpoint de actualización (`PATCH /pictograms/{id}`) usaba el mismo `FormRequest` que la creación (`StorePictogramRequest`), con reglas `required` en todos los campos. Resultado: para cambiar solo `isActive`, había que reenviar `title`, `phrase`, `emoji`, todo — exactamente lo contrario de lo que significa "actualización parcial".

**La solución:** un `FormRequest` separado (`UpdatePictogramRequest`) con reglas `sometimes` en vez de `required`. `sometimes` le dice a Laravel: *"valida este campo solo si el cliente lo mandó; si no vino, ignóralo por completo"*.

```php
// Antes (Store, usado también en Update — INCORRECTO):
'title' => ['required', 'string', 'max:100'],

// Después (Update, correcto):
'title' => ['sometimes', 'string', 'max:100'],
```

**Analogía:** es la diferencia entre rellenar un formulario completo de nuevo (`PUT`/`Store`) y solo tachar una casilla en un formulario que ya existe (`PATCH`).

### 3.2 Policies sin type-hint cuando deben aceptar más de un tipo de usuario

**El bug:** `viewAny(User $user): bool` — cuando un `Patient` (no `User`) llamaba a este método, PHP lanzaba un `TypeError` fatal, no un `403` controlado, porque `Patient` no es instancia de `User`.

**La solución:** quitar el type-hint cuando el método debe aceptar identidades de más de una clase.

```php
// Rompe con Patient:
public function viewAny(User $user): bool { return true; }

// Acepta cualquier identidad autenticada:
public function viewAny($user): bool { return true; }
```

**Regla general:** si un endpoint es accesible tanto por `User` (staff) como por `Patient`, ningún método de la Policy involucrado puede tener type-hint estricto de `User`.

### 3.3 `$fillable` es una lista blanca — y Laravel no avisa si algo queda fuera

**El bug:** al crear una categoría con `PictogramCategory::create([...])`, el campo `is_active` llegaba en el array, pero como no estaba declarado en `$fillable` del modelo, Laravel lo **descartaba en silencio**. El registro se creaba con `isActive: null` en vez de `true`, sin ningún error visible.

```php
// Antes: is_active no está permitido, se ignora silenciosamente
protected $fillable = ['name', 'sort_order'];

// Después:
protected $fillable = ['name', 'sort_order', 'is_active'];
protected $casts = ['is_active' => 'boolean'];
```

**Por qué es peligroso:** no lanza excepción, no aparece en los logs — el dato simplemente "desaparece". Es uno de los bugs más difíciles de detectar sin pruebas reales, porque el código "se ve bien" a simple vista.

### 3.4 Los controllers de Laravel 11+ ya no traen todo por defecto

**El bug:** `$this->validate($request, [...])` — este método existía automáticamente en controllers de versiones antiguas de Laravel (heredado de un trait incluido por defecto). Desde Laravel 11, el controller base es minimalista y ese método ya no está disponible.

**La solución:** usar el `Validator` facade explícitamente.

```php
// No funciona en Laravel 11+:
$this->validate($request, ['search' => 'min:2']);

// Sí funciona:
Validator::make($request->only('search'), ['search' => 'min:2'])->validate();
```

### 3.5 "Eliminar" no siempre significa borrar de la base de datos

Un pictograma puede estar referenciado por mensajes de chat de atenciones ya cerradas — ese historial es evidencia clínico-legal y no puede quedar roto. Por eso, igual que en Organizaciones/Centros/Unidades desde la Fase 4, "eliminar" = `is_active = false`, nunca `DELETE FROM`.

## 4. Endpoints construidos

| Endpoint | Verbo | Qué hace |
|---|---|---|
| `/pictograms` | `GET` | Lista con `search`, `sort`, `includeInactive` |
| `/pictograms` | `POST` | Crear (ya existía) |
| `/pictograms/{id}` | `PATCH` | Actualización parcial real |
| `/pictograms/{id}` | `DELETE` | Desactivar |
| `/pictograms/{id}/restore` | `PATCH` | Reactivar |
| `/pictogram-categories` | `GET/POST/PATCH/DELETE/restore` | CRUD completo |

## 5. Cómo se verificó

10 pruebas ejecutadas desde Swagger con evidencia HTTP real, incluyendo casos negativos:

- `403` cuando `super_admin` intenta gestionar pictogramas (confirma que la regla de negocio se cumple en ejecución, no solo en el diseño).
- `422` cuando se intenta crear una categoría con nombre duplicado.
- Ciclo completo crear → desactivar → reactivar, tanto en pictogramas como en categorías.

## 6. Resumen — qué aprendí

1. `PATCH` exige reglas de validación distintas a `POST`: `sometimes`, no `required`.
2. Las Policies que deben servir a más de un tipo de usuario no pueden type-hintear la clase concreta.
3. `$fillable` falla en silencio — es una fuente de bugs invisible si no se prueba con datos reales.
4. Las versiones nuevas de Laravel cambian qué viene "gratis" en el controller base; no asumir que un método viejo sigue existiendo.
5. Desactivar en vez de borrar protege la integridad de datos históricos referenciados desde otras tablas.
6. Ningún bug de este hito se habría detectado solo leyendo el código — todos aparecieron al ejecutar el endpoint real.
