# 🎯 Guía de Estudio — Fase 4 · Hito A
## CRUD completo de Usuarios (con Policies)

---

## 🎯 ¿Qué hicimos en este hito?

Completamos las 4 operaciones que le faltaban al módulo de usuarios — antes solo se podía **crear**, ahora también se puede **listar, ver uno, editar y desactivar**. Y de paso, aprendimos una herramienta de Laravel que va a ahorrarnos mucho trabajo en los hitos que vienen: las **Policies**.

---

## 🧩 Parte A — Los 4 endpoints nuevos

### Lo que existía vs lo que construimos

```
Ya existía:
  POST   /users          → Crear                    ✅

Construimos:
  GET    /users          → Listar todos             ✅
  GET    /users/{id}     → Ver uno en detalle       ✅
  PUT    /users/{id}     → Editar                   ✅
  DELETE /users/{id}     → Desactivar (soft delete) ✅
```

### El flujo de cada uno

**Listar (`GET /users`)**
> El admin abre la pantalla → el servidor filtra según quién pregunta (super_admin ve todos, admin_institucional solo los de su centro) → se pinta la tabla.

**Ver uno (`GET /users/{id}`)**
> Clic en una fila → se pide ese usuario por su ID → el servidor verifica permiso → lo devuelve.

**Editar (`PUT /users/{id}`)**
> Se cambia algún dato → se envía solo lo que cambió → el servidor valida y actualiza → devuelve el usuario actualizado.

**Desactivar (`DELETE /users/{id}`)**
> Clic en "eliminar" → el servidor **no borra la fila**, solo pone `is_active = false` → el usuario no puede loguearse, pero su historial se conserva.

### 💡 Soft delete vs Hard delete

| | Hard delete | Soft delete (el que usamos) |
|---|---|---|
| Qué hace | Borra la fila de la BD | Marca `is_active = false` |
| ¿Se recupera? | No | Sí, con reactivarlo basta |
| Registros relacionados | Quedan huérfanos | Siguen intactos |
| En un sistema de salud | Riesgoso | Correcto |

**Lo probamos en vivo:** después de desactivar un usuario, volvimos a pedir su detalle (`GET /users/{id}`) y **seguía existiendo completo**, solo con `isActive: false`. Esa es la prueba de que el soft delete funciona de verdad.

### 💡 Route Model Binding

Con la ruta `/users/{user}`, Laravel **busca automáticamente** el usuario en la base de datos y te lo entrega ya listo:

```php
// Antes tendrías que escribir esto:
public function show($id) {
    $user = User::find($id);
    if (!$user) return response()->json([...], 404);
}

// Con binding, Laravel lo hace solo:
public function show(User $user) {
    // $user ya está listo aquí, y si no existe, Laravel devuelve 404 solo
}
```

**Requisito:** el nombre del parámetro en la ruta (`{user}`) debe coincidir con el nombre de la variable en el método (`User $user`).

### 💡 La regla `'sometimes'`

Para permitir actualizaciones parciales (editar solo el nombre, sin tener que reenviar todo):

```php
$data = $request->validate([
    'name'  => ['sometimes', 'string', 'max:255'],
    'email' => ['sometimes', 'email'],
]);
```

`sometimes` significa: *"valida este campo solo si vino en la petición"*. Sin eso, `PUT` exigiría reenviar el objeto completo cada vez.

---

## 🛡️ Parte B — Policies: eliminando código repetido

### El problema que encontramos

Al escribir `show`, `update` y `destroy`, cada uno repetía **la misma lógica de permisos**:

```php
if (! in_array($admin->role, ['super_admin', 'admin_institucional'])) {
    return response()->json([...], 403);
}
if ($admin->role === 'admin_institucional' && $user->health_center_id !== $admin->health_center_id) {
    return response()->json([...], 403);
}
```

**Copiado 4 veces.** Si mañana cambia la regla, hay que acordarse de actualizarla en los 4 lugares — y si se olvida uno, queda un hueco de seguridad silencioso.

> 💡 **Analogía:** es como tener un guardia distinto en cada habitación de la casa, todos repitiendo las mismas reglas de memoria. Mejor un solo guardia en la puerta principal que ya las conoce todas.

### La solución: una Policy

Una **Policy** es una clase dedicada a responder: *"¿esta persona puede hacer esta acción sobre este recurso?"*

```powershell
php artisan make:policy UserPolicy --model=User
```

Esto genera 7 métodos, **todos devolviendo `false` por defecto**:

```php
public function view(User $user, User $model): bool
{
    return false;
}
```

### 💡 Por qué Laravel genera todo en `false`

Es la filosofía de **"denegar por defecto"** (*deny by default*): si te olvidas de programar la lógica de un método nuevo, el resultado es que **nadie puede hacer esa acción** — un error seguro, no un hueco abierto.

> 💡 **Analogía:** ¿prefieres que todas las puertas de una casa nueva vengan cerradas con llave y tú decidas cuáles abrir, o que vengan todas abiertas y tengas que acordarte de cerrar cada una? La primera opción es más segura.

### Los dos parámetros de cada método

```php
public function view(User $user, User $model): bool
```

- **`$user`** → quien pregunta (el admin logueado)
- **`$model`** → sobre quién se pregunta (el usuario que se quiere ver/editar)

### El mapeo acción → método

| Acción del controlador | Método de la Policy |
|---|---|
| Listar (`index`) | `viewAny` |
| Ver uno (`show`) | `view` |
| Crear (`register`) | `create` |
| Editar (`update`) | `update` |
| Desactivar (`destroy`) | `delete` |

Estos nombres **no son arbitrarios** — es la convención estándar de Laravel.

### El resultado: controladores mucho más simples

**Antes** (repetido en 4 métodos):
```php
if (! in_array($admin->role, ['super_admin', 'admin_institucional'])) {
    return response()->json([...], 403);
}
if ($admin->role === 'admin_institucional' && $user->health_center_id !== $admin->health_center_id) {
    return response()->json([...], 403);
}
// ... y en destroy, además:
if ($admin->id === $user->id) {
    return response()->json([...], 403);
}
```

**Ahora** (una línea, en cada método):
```php
$this->authorize('delete', $user);
```

Si la Policy dice "no", Laravel **lanza automáticamente** un `403 Forbidden` — no hay que escribirlo a mano.

### El caso especial de `create`

`viewAny`, `view`, `update` y `delete` reciben un `$model` real para comparar el centro de salud. Pero `create` **no puede** — el usuario todavía no existe, no hay nada que comparar.

Por eso `create` en la Policy solo valida el **rol**, y la comprobación de "en qué centro específico" se queda en el controlador, porque depende de un dato que llega en el body de la petición, no de un modelo ya guardado.

### Detalle técnico: activar `authorize()`

En Laravel 13 (API-only), el `Controller` base **no trae por defecto** la capacidad de usar `$this->authorize(...)`. Hubo que agregarla explícitamente:

```php
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;
}
```

> 💡 **Sobre el `use` dentro de la clase:** es distinto al `use` de importación de arriba. Este significa "incorporar este trait aquí" — como decirle a `Controller`: "toma prestadas todas las funciones de `AuthorizesRequests`, como si las hubiera escrito yo mismo".

---

## 🧪 Lo que probamos en Swagger

| Endpoint | Resultado |
|---|---|
| `GET /users` | `200` — lista completa |
| `GET /users/{id}` | `200` — detalle de un usuario |
| `PUT /users/{id}` | `200` — actualización parcial exitosa |
| `DELETE /users/{id}` | `200` — `isActive: false` |
| `GET /users/{id}` (después del delete) | `200` — el registro **sigue existiendo**, confirmando el soft delete |

---

## 🎓 Las 3 lecciones grandes

### 1. El código repetido es una señal, no una casualidad
Cuando ves la misma lógica copiada varias veces, es momento de preguntarse si existe una herramienta del framework diseñada justo para eso. En Laravel, para autorización, esa herramienta son las Policies.

### 2. Seguridad por diseño, no por costumbre
"Denegar por defecto" no es solo una buena práctica — es una decisión que hace que los errores futuros sean **seguros** en vez de **peligrosos**.

### 3. Separar "puede hacer la acción" de "los datos son válidos"
La Policy responde una pregunta (¿tiene permiso?); las reglas de `validate()` responden otra (¿los datos están bien formados?). Mezclarlas hace el código más difícil de mantener.

---

## ▶️ Lo que viene

Con el patrón de CRUD + Policy ya dominado, los siguientes hitos van a ser más rápidos porque se repite la misma estructura:

| Hito | Qué construye |
|:---:|---|
| **B** | CRUD completo de **Organizaciones** |
| **C** | CRUD completo de **Centros de Salud** |
| **D** | CRUD completo de **Unidades** |
| **1** | Modelo **Paciente** (retomando el núcleo clínico) |

---

*Guía de estudio · Proyecto SeñaVida · Fase 4 · Hito A*
