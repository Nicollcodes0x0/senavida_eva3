# 🎯 Guía de Estudio — Fase 4 · Hitos B, C, D
## CRUD completo de Organizaciones, Centros de Salud y Unidades

---

## 🎯 ¿Qué hicimos en estos tres hitos?

Completamos el CRUD de las 3 entidades de catálogo que faltaban. Ya en el Hito A habías aprendido el patrón completo (endpoints + Policy) con Usuarios — estos tres hitos fueron aplicar ese mismo patrón, entidad por entidad, notando en qué se parecen y en qué se diferencian.

```
Hito A (ya hecho) → Usuarios         → con multitenancy
Hito B            → Organizaciones   → sin multitenancy (nivel más alto)
Hito C            → Centros de Salud → sin multitenancy (solo super_admin)
Hito D            → Unidades         → con multitenancy (como Usuarios)
```

---

## 🏗️ Parte A — El patrón que se repitió 3 veces

Para cada entidad, seguimos siempre los mismos 4 pasos:

1. **Crear la Policy** con `php artisan make:policy {Nombre}Policy --model={Modelo}`
2. **Llenar la Policy** con las reglas reales (`viewAny`, `view`, `create`, `update`, `delete`)
3. **Agregar las rutas** nuevas en `routes/api.php`
4. **Agregar los métodos** al controlador (`show`, `update`, `destroy`), usando `$this->authorize(...)`

### La estructura de cada Policy

```php
class {Nombre}Policy
{
    public function viewAny(User $user): bool { ... }           // ¿puede listar?
    public function view(User $user, {Modelo} $model): bool { ... }    // ¿puede ver uno?
    public function create(User $user): bool { ... }             // ¿puede crear?
    public function update(User $user, {Modelo} $model): bool { ... }  // ¿puede editar?
    public function delete(User $user, {Modelo} $model): bool { ... }  // ¿puede desactivar?
}
```

### La estructura de cada endpoint nuevo en el controlador

```php
public function show({Modelo} $model): JsonResponse
{
    $this->authorize('view', $model);   // una sola línea reemplaza toda la validación repetida

    return response()->json([...], 200);
}
```

---

## 🔍 Parte B — Las diferencias entre entidades (lo importante de aprender)

Aunque el patrón se repite, **las reglas de negocio cambian**, y eso es lo que hace que cada Policy sea distinta.

### Tabla comparativa de permisos

| Acción | Usuarios (Hito A) | Organizaciones (Hito B) | Centros de Salud (Hito C) | Unidades (Hito D) |
|---|---|---|---|---|
| **Ver (listar/uno)** | `super_admin` + `admin_institucional` | `super_admin` + `admin_institucional` | `super_admin` + `admin_institucional` | `super_admin` + `admin_institucional` |
| **Crear** | Ambos roles | Solo `super_admin` | Solo `super_admin` | Ambos roles |
| **Editar** | Ambos roles (con multitenancy) | Solo `super_admin` | Solo `super_admin` | Ambos roles (con multitenancy) |
| **Desactivar** | Ambos roles (con multitenancy) | Solo `super_admin` | Solo `super_admin` | Ambos roles (con multitenancy) |
| **¿Tiene multitenancy?** | ✅ Sí | ❌ No | ❌ No | ✅ Sí |
| **¿Autobloqueo bloqueado?** | ✅ Sí | No aplica | No aplica | No aplica |

### 💡 Por qué Organizaciones y Centros son "solo super_admin"

Ambas son entidades **estructurales de alto nivel** — modificarlas afecta a todo lo que cuelga debajo (centros, unidades, usuarios). Dejar que un `admin_institucional` (que solo debería preocuparse de *su* centro) edite la organización completa, o cree/edite *otros* centros, sería darle poder fuera de su alcance real.

### 💡 Por qué Usuarios y Unidades sí tienen multitenancy

Son entidades donde tiene sentido que un `admin_institucional` opere **dentro de su propio territorio**: gestionar el personal de su hospital, gestionar las salas de su hospital. Por eso ambas Policies comparten esta misma forma:

```php
public function update(User $user, {Modelo} $model): bool
{
    if (! in_array($user->role, ['super_admin', 'admin_institucional'])) {
        return false;
    }

    if ($user->role === 'admin_institucional') {
        return $model->health_center_id === $user->health_center_id;
    }

    return true; // super_admin puede editar cualquiera
}
```

### 💡 El caso especial de `create`

En las 4 Policies, `create()` **solo valida el rol**, nunca la ubicación específica:

```php
public function create(User $user): bool
{
    return in_array($user->role, ['super_admin', 'admin_institucional']);
}
```

Esto es así porque, al crear, **todavía no existe un `$model` real** con el cual comparar el centro de salud — el dato llega recién en el `body` de la petición. Por eso, la comprobación de "¿en qué centro específico quiere crear?" se queda como una validación aparte, dentro del método `store()` del controlador, no en la Policy.

---

## 🐛 Parte C — El error que encontramos (y cómo lo diagnosticamos)

### El síntoma

Al probar `GET /health-centers/{id}` en Swagger, la respuesta fue:

```json
{
  "message": "The route api/v1/health-centers/xxxx could not be found."
}
```

Un `404`, aunque habíamos escrito la ruta.

### El diagnóstico, paso a paso

**1. Verificar qué rutas existen realmente:**
```powershell
php artisan route:list --path=health-centers
```
Resultado: solo aparecían 2 rutas (`index`, `store`), en vez de las 5 esperadas.

**2. Descartar la caché:**
```powershell
php artisan route:clear
```
No cambió nada — así que no era un problema de caché vieja.

**3. Revisar el archivo directamente:**
```powershell
Get-Content routes/api.php
```
Ahí se vio la causa real: **las 3 líneas nuevas de `health-centers` no se habían guardado** en el archivo — se perdieron al copiar y pegar.

### La lección clave

> 💡 **No asumas que un archivo se guardó como esperabas — verifícalo.** Por eso, desde el Hito D en adelante, adoptamos una nueva costumbre: correr `route:list` **inmediatamente después** de editar las rutas, antes de perder tiempo probando en Swagger. Encontrar un problema apenas ocurre es mucho más barato que encontrarlo varios pasos después.

---

## 🎓 Concepto reforzado: Router vs Controlador

Surgió la duda de qué hace cada uno, y vale la pena dejarlo anotado:

| | Router (`routes/api.php`) | Controlador (`{X}Controller.php`) |
|---|---|---|
| Responde | "¿A DÓNDE va esta petición?" | "¿QUÉ hay que hacer?" |
| Analogía | El mesero que recibe el pedido | El cocinero que lo prepara |
| Contiene | Solo el mapa URL + verbo → método | Toda la lógica: permisos, validación, base de datos |

```
1. Llega una petición
2. El ROUTER decide a qué método del controlador mandarla
3. Laravel hace Route Model Binding (busca el modelo por su ID)
4. El CONTROLADOR ejecuta la lógica real y responde
```

### Cómo diferenciar rutas parecidas

Dos ejes determinan a qué método va cada petición:

**Eje 1 — La URL:**
```
/units          ← "todas las unidades" (una lista)
/units/{unit}   ← "una unidad puntual"
```

**Eje 2 — El verbo HTTP:**
```
GET    → "quiero LEER"
POST   → "quiero CREAR"
PUT    → "quiero ACTUALIZAR"
DELETE → "quiero ELIMINAR/DESACTIVAR"
```

Es la combinación **verbo + URL** la que define la ruta única — por eso `/units/{unit}` puede tener 3 métodos distintos (`GET`, `PUT`, `DELETE`) apuntando a acciones diferentes.

---

## 🎓 Concepto reforzado: autobloqueo (lockout prevention)

Repasamos por qué `UserPolicy::delete()` bloquea que alguien se desactive a sí mismo:

> Si el único `super_admin` del sistema se desactivara a sí mismo, **nadie quedaría con permiso para reactivarlo** — el sistema quedaría sin administrador funcional, como quedar encerrado afuera de tu propia casa sin llaves de repuesto.

Por eso ese chequeo va **primero**, sin excepciones, incluso para el rol más alto.

Y quedó claro **por qué ese chequeo no aplica** a Organizaciones, Centros ni Unidades: esas entidades no representan una identidad que inicia sesión — el concepto de "autobloqueo" solo tiene sentido para usuarios.

---

## 🧪 Lo que se probó en Swagger (los 3 hitos)

Para cada entidad, se repitió esta secuencia con éxito:

1. `GET` (listar) → confirma `isActive` visible
2. `GET /{id}` (ver una) → confirma detalle correcto
3. `PUT /{id}` (editar) → confirma actualización parcial
4. `DELETE /{id}` (desactivar) → confirma `isActive: false`
5. `GET /{id}` otra vez → confirma que el registro **sigue existiendo**, probando el soft delete

---

## 🎓 Las 3 lecciones grandes de este bloque

### 1. Un patrón bien aprendido se vuelve rápido de repetir
El Hito A tomó más tiempo porque incluía aprender qué es una Policy. Los Hitos B, C y D fueron mucho más veloces porque ya se entendía la estructura — solo cambiaban las reglas específicas de cada entidad.

### 2. Las reglas de negocio no son "una talla única"
No todas las entidades merecen el mismo nivel de acceso. Pensar "¿quién debería poder hacer esto, y por qué?" para cada caso es más importante que copiar la misma Policy sin pensar.

### 3. Verificar es más barato que asumir
El error del Hito C (rutas faltantes) se resolvió rápido porque se usó `route:list` para **ver la realidad**, en vez de asumir que el archivo se había guardado bien. Esa costumbre se mantiene desde entonces.

---

## 📊 Estado del proyecto tras estos hitos

| Hito | Qué construye | Estado |
|:---:|---|:---:|
| **0** | Saneamiento técnico + Swagger | ✅ |
| **A** | CRUD completo de Usuarios | ✅ |
| **B** | CRUD completo de Organizaciones | ✅ |
| **C** | CRUD completo de Centros de Salud | ✅ |
| **D** | CRUD completo de Unidades | ✅ |
| **1** | Modelo Paciente | ⏭️ Siguiente |
| **2** | CTA (Código Temporal de Atención) | Pendiente |
| **3** | Sesión Médica | Pendiente |
| **4** | Middleware de sesión activa | Pendiente |

---

## ▶️ Lo que viene

Con el módulo administrativo 100% completo (backend), y cumpliendo de sobra el requisito del EVA3 de "al menos un módulo con CRUD completo", retomamos el **núcleo clínico real** del proyecto: el **Hito 1 — Modelo Paciente**.

---

*Guía de estudio · Proyecto SeñaVida · Fase 4 · Hitos B, C y D*
