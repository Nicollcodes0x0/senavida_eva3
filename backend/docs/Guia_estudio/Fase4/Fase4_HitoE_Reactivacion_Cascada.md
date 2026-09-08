# 🎯 Guía de Estudio — Fase 4 · Hito E
## Reactivación y protección de jerarquía

---

## 🎯 ¿Qué hicimos en este hito?

Cerramos tres huecos que quedaron abiertos después de construir el CRUD completo de las 4 entidades administrativas:

1. **Visibilidad:** ahora se puede ver qué registros están desactivados.
2. **Reactivación:** ahora existe un endpoint para revertir una desactivación.
3. **Protección en cascada:** ahora el sistema **rechaza** activamente desactivar algo que tiene hijos activos dependiendo de ello.

---

## 🔍 Parte A — El filtro de visibilidad

### El problema que resolvimos

Antes, todos los `index()` tenían esto fijo:

```php
$query = Unit::where('is_active', true);
```

Una vez que desactivabas un registro, **desaparecía por completo** de cualquier listado. No había forma de encontrarlo de nuevo salvo que tuvieras su `id` guardado de antes.

### La solución

Agregamos un parámetro de consulta opcional:

```php
$status = $request->query('status', 'active');

if ($status === 'active') {
    $query->where('is_active', true);
} elseif ($status === 'inactive') {
    $query->where('is_active', false);
}
// 'all' no agrega ningún filtro → trae todo
```

```
GET /units                    → solo activas (comportamiento por defecto, igual que antes)
GET /units?status=inactive    → solo las desactivadas
GET /units?status=all         → todas, sin importar el estado
```

> 💡 **Por qué el valor por defecto es `'active'`:** así, cualquier código que ya llamaba a estos endpoints **sigue funcionando exactamente igual** que antes de este hito — no rompimos nada existente, solo agregamos una capacidad nueva opcional. Esto se llama **compatibilidad hacia atrás** (*backward compatibility*).

---

## 🔄 Parte B — Reactivación con `PATCH .../restore`

### El descubrimiento: ya teníamos el método, sin usar

Cuando generamos las Policies con `make:policy` en los hitos anteriores, Laravel **ya nos había regalado** un método llamado `restore()` en cada una — pero todas devolvían `false` sin excepción, porque no lo estábamos usando todavía.

```php
public function restore(User $user, User $model): bool
{
    return false;   // esto ya estaba ahí desde el principio
}
```

Solo tuvimos que **llenarlo** con la misma lógica que ya tenía `delete()` en cada Policy — no inventamos nada nuevo, reutilizamos un patrón ya aprendido.

### El nuevo verbo: `PATCH`

Hasta este hito solo habías usado `GET`, `POST`, `PUT` y `DELETE`. Ahora aparece `PATCH`:

| Verbo | Cuándo usarlo |
|---|---|
| `PUT` | Reemplazar/actualizar **datos** de un recurso |
| `PATCH` | Hacer un **cambio puntual de estado**, no una edición de datos |

Reactivar un registro no es "editarlo" — es cambiar su estado de apagado a encendido. Por convención REST, ese tipo de acción específica usa `PATCH`, y por eso la ruta lleva un sufijo descriptivo:

```
PATCH /units/{id}/restore
```

### Simetría de permisos

Aplicamos la misma regla en `restore()` que ya existía en `delete()` — porque tiene sentido que **quien puede apagar algo, también pueda encenderlo de vuelta**:

| Entidad | Quién puede reactivar |
|---|---|
| Usuarios | `super_admin` + `admin_institucional` (su propio centro) |
| Unidades | `super_admin` + `admin_institucional` (su propio centro) |
| Centros de Salud | Solo `super_admin` |
| Organizaciones | Solo `super_admin` |

---

## 🛡️ Parte C — Protección en cascada (la pieza más importante)

### El caso límite que la originó

Surgió una pregunta clave: *"¿qué pasaría si un `super_admin` desactiva una Organización por error?"*

Rastreamos qué pasaba con el código de antes: **nada se propagaba**. La organización quedaba inactiva, pero todos sus centros, unidades y usuarios seguían funcionando con total normalidad — incluyendo que los usuarios podían seguir haciendo login. El sistema quedaba en un estado **inconsistente**: una organización "apagada" con hijos "encendidos" colgando de ella.

### Las alternativas que consideramos

| Opción | Descripción | Por qué se descartó / eligió |
|---|---|---|
| No hacer nada | Dejar el comportamiento actual | ❌ El error queda silencioso, nadie se entera |
| Cascada automática | Desactivar todo lo de abajo automáticamente | ❌ Complica demasiado el "deshacer" si fue un error |
| **Bloquear la operación** | Rechazar la desactivación si hay hijos activos | ✅ Elegida — previene el error antes de que ocurra |

### La implementación

Antes de desactivar, **contamos** cuántos hijos activos existen:

```php
public function destroy(Organization $organization): JsonResponse
{
    $this->authorize('delete', $organization);

    $centrosActivos = $organization->healthCenters()->where('is_active', true)->count();

    if ($centrosActivos > 0) {
        return response()->json([
            'success' => false,
            'error'   => ['message' => "No puedes desactivar esta organizacion porque tiene {$centrosActivos} centro(s) de salud activo(s). Desactivalos primero."],
        ], 409);
    }

    $organization->is_active = false;
    $organization->save();
    // ...
}
```

Este mismo patrón se repite en 3 niveles de la jerarquía:

```
Organización → bloqueada si tiene Centros de Salud ACTIVOS
Centro       → bloqueado si tiene Unidades ACTIVAS
Unidad       → bloqueada si tiene Usuarios ACTIVOS
```

Cada nivel protege al que tiene inmediatamente debajo — una cadena de seguridad de arriba hacia abajo.

### 💡 Por qué el código de respuesta es `409`, no `403` ni `422`

Elegimos específicamente `409 Conflict`, y no otro código, porque cada uno significa algo distinto:

| Código | Significa |
|---|---|
| `403 Forbidden` | "No tienes **permiso** para hacer esto" |
| `422 Unprocessable` | "Los **datos que enviaste** están mal formados" |
| `409 Conflict` | "Tienes permiso y los datos están bien, pero **el estado actual del recurso** impide completar la acción" |

Aquí sí tenías permiso (`super_admin` puede desactivar organizaciones) y no enviaste ningún dato mal formado — el problema es que el **estado actual** (tiene hijos activos) hace que la operación no se pueda completar en este momento. Por eso `409` es semánticamente el código correcto.

### Confirmado en vivo

Al intentar desactivar una organización con un centro activo:

```json
{
  "success": false,
  "error": {
    "message": "No puedes desactivar esta organizacion porque tiene 1 centro(s) de salud activo(s). Desactivalos primero."
  }
}
```

`409 Conflict` — el sistema rechazó activamente la operación, en vez de permitir un estado inconsistente.

---

## 🐛 Un tropiezo que vale la pena registrar

Al pegar el método `restore()` en `UserController.php`, el bloque nuevo quedó **dentro** del cuerpo de `index()` en vez de después de su cierre — un error de conteo de llaves `{ }` fácil de cometer cuando el código tiene muchas anidadas.

### Cómo lo detectamos

```powershell
php -l ruta/al/archivo.php
```

Este comando le pide a PHP que revise la **sintaxis** del archivo sin ejecutarlo. Si algo está mal estructurado (como una función dentro de otra), lo dice de inmediato, con el número de línea exacto.

> 💡 **La costumbre que se consolidó en este hito:** correr `php -l` **inmediatamente después** de reemplazar cada archivo, antes de intentar generar Swagger o probar en el navegador. Encontrar un error de sintaxis en 2 segundos con `php -l` es mucho más eficiente que descubrirlo después con un error confuso en otra parte del sistema.

---

## 🎓 Las 3 lecciones grandes de este hito

### 1. A veces la herramienta ya está ahí, solo hay que activarla
El método `restore()` existía desde el primer `make:policy` de cada entidad. No hizo falta inventar nada — solo reconocer que ya estaba preparado y darle contenido real.

### 2. Prevenir es mejor que lidiar con las consecuencias
Ante la duda de "cascada automática" vs "bloquear la operación", elegimos la opción que **evita que el error ocurra**, en vez de la que intenta arreglarlo después. Es más seguro, especialmente en un sistema de salud.

### 3. Los códigos HTTP tienen significado semántico
No todos los "no se pudo" son iguales. Elegir `409` en vez de `403` o `422` no es un detalle cosmético — le dice con precisión a quien consuma la API (Nicoll, o cualquier otro desarrollador) *por qué* falló la operación, sin tener que leer el mensaje de texto para entenderlo.

---

## 📊 Estado final del módulo administrativo

| Hito | Qué construye | Estado |
|:---:|---|:---:|
| **0** | Saneamiento técnico + Swagger | ✅ |
| **A** | CRUD completo de Usuarios | ✅ |
| **B** | CRUD completo de Organizaciones | ✅ |
| **C** | CRUD completo de Centros de Salud | ✅ |
| **D** | CRUD completo de Unidades | ✅ |
| **E** | Reactivación + protección en cascada | ✅ |
| **1** | Modelo Paciente | ⏭️ Siguiente |
| **2** | CTA (Código Temporal de Atención) | Pendiente |
| **3** | Sesión Médica | Pendiente |
| **4** | Middleware de sesión activa | Pendiente |

**24 endpoints en total**, 4 Policies completas con sus 7 métodos cada una, filtros de visibilidad consistentes, y protección activa contra estados inconsistentes en toda la jerarquía.

---

## ▶️ Lo que viene

Con el módulo administrativo completamente robusto — no solo funcional, sino protegido contra errores humanos — retomamos el **núcleo clínico real**: el **Hito 1 — Modelo Paciente**.

---

*Guía de estudio · Proyecto SeñaVida · Fase 4 · Hito E*
