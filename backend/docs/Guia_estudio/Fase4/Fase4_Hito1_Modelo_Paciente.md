# 🎯 Guía de Estudio — Fase 4 · Hito 1
## Modelo Paciente

---

## 🎯 ¿Qué hicimos en este hito?

Construimos la primera pieza del **núcleo clínico** de SeñaVida: el modelo `Patient`, con una particularidad que no habíamos visto en ningún hito anterior — **el paciente se autorregistra**, sin token, sin cuenta previa. Es el primer endpoint verdaderamente público de todo el backend (además del login).

De paso, resolvimos una mejora que beneficia a todo el proyecto: los mensajes de validación ahora salen en español.

---

## 🏥 Parte A — Diseñando el modelo desde la especificación real

### La regla de oro que encontramos en tu propio contrato

Antes de escribir una sola línea de código, revisamos tu `FRONTEND_BACKEND_CONTRACT.md` y encontramos algo explícito y estricto:

> *"Responsable: El propio paciente. NINGÚN rol clínico puede modificar esta entidad."*
> *"El backend NO DEBE exponer ningún recurso de escritura sobre Patient accesible a roles clínicos."*

Esto cambió por completo cómo pensamos el diseño. No era "¿qué rol puede crear pacientes?" — era **"nadie del personal puede, punto"**.

### El problema lógico que resolvimos conversando

Si nadie del personal puede crear pacientes, pero el paciente tampoco tiene cuenta todavía... **¿quién crea el primer registro?**

La respuesta que decidimos: **el paciente se autorregistra**, desde un formulario propio, sin necesitar sesión iniciada. Esto llevó a una consecuencia arquitectónica importante: `POST /patients` tenía que vivir **fuera** del grupo `auth:sanctum`.

### Los campos, extraídos de tu `RF-016`

No inventamos el diseño — lo sacamos directo de tu especificación:

```
nombre, identificación (RUT/pasaporte), fecha de nacimiento,
previsión de salud, dirección, teléfono, CESFAM,
alergias, condiciones de salud, preferencia de comunicación
```

Con una corrección explícita que también estaba en tu contrato:

> ⚠️ *"La edad NO DEBE almacenarse: se deriva de la fecha de nacimiento."*

---

## 🧮 Parte B — El accessor de edad

### El problema de guardar la edad directamente

Si guardaras `age = 28` como columna, ese número quedaría **congelado** — el año que viene seguiría diciendo 28, aunque el paciente ya haya cumplido 29. Una columna de edad se desactualiza sola con el tiempo.

### La solución: calcularla al vuelo

```php
protected function age(): Attribute
{
    return Attribute::make(
        get: fn () => $this->birth_date->age,
    );
}
```

Esto se llama un **accessor** — un atributo "virtual" que **no existe como columna en la base de datos**, pero que se comporta como si existiera cuando lo consultas: `$patient->age`.

> 💡 **Por qué funciona `$this->birth_date->age`:** en el modelo, declaramos `'birth_date' => 'date'` en los `$casts`. Eso le dice a Laravel que trate esa columna como un objeto de fecha real (usando la librería Carbon), no como texto plano. Carbon trae `->age` incorporado, que calcula la diferencia entre esa fecha y hoy automáticamente.

### Confirmado en vivo

Probamos con un paciente nacido en 1997-11-15, y `$patient->age` devolvió `28` — correcto para la fecha actual del proyecto (agosto 2026).

---

## 🔓 Parte C — El primer endpoint público del proyecto

### Por qué es distinto a todo lo anterior

Hasta este hito, cada endpoint vivía dentro de:

```php
Route::middleware('auth:sanctum')->group(function () { ... });
```

`POST /patients` quedó **fuera** de ese grupo, al mismo nivel que el login:

```php
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/patients', [PatientController::class, 'store']);   // ← también público

Route::middleware('auth:sanctum')->group(function () {
    // todo lo demás, incluyendo GET /patients y GET /patients/{id}
});
```

### El riesgo de un endpoint público que escribe datos

Un endpoint que **crea** información sin pedir credenciales es un blanco típico de abuso — bots que lo saturan con registros falsos. Por eso le aplicamos **rate limiting**, el mismo mecanismo que ya usaste en el login:

```php
$throttleKey = 'patient-register:'.$request->ip();

if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
    // bloquear con 429
}
```

Aquí limitamos por **IP** (no por email, porque el paciente no tiene cuenta) — máximo 5 registros cada 10 minutos desde la misma dirección.

### Confirmado en vivo

Probamos `POST /patients` **sin ningún header `Authorization`** en el `curl` generado por Swagger, y funcionó con `201 Created`. Confirmamos también que `national_id` es único: un segundo intento con el mismo RUT (aunque con nombre y datos distintos) fue rechazado con `422`.

---

## 🛡️ Parte D — La Policy más restrictiva del proyecto

### Por qué rompe el patrón de las Policies anteriores

Todas tus Policies anteriores (Usuarios, Organizaciones, Centros, Unidades) tenían la forma *"¿qué rol SÍ puede hacer esto?"*. `PatientPolicy` es distinta: para crear, editar y borrar, la respuesta es **siempre no**, sin excepción de rol:

```php
public function create(User $user): bool
{
    return false;   // nadie del personal, nunca — ni siquiera super_admin
}

public function update(User $user, Patient $patient): bool
{
    return false;   // tampoco
}

public function delete(User $user, Patient $patient): bool
{
    return false;   // tampoco
}
```

Solo `view`/`viewAny` tienen una regla real, permitiendo que el personal clínico **lea** la ficha (para poder atender al paciente), sin poder modificarla:

```php
public function view(User $user, Patient $patient): bool
{
    return in_array($user->role, ['admision', 'categorizacion', 'medico', 'super_admin']);
}
```

### Por qué documentamos el "porqué" en los `false`

Cuando un método devuelve `false` sin condiciones, alguien que lea el código después podría pensar que está incompleto. Por eso dejamos comentarios explicando que es **intencional**, citando la regla del contrato — así nadie lo "arregla" por error en el futuro pensando que faltó terminar la lógica.

### Confirmado en vivo, con el rol más alto del sistema

```php
$user->can('view', $patient);    // true  — sí puede leer
$user->can('update', $patient);  // false — ni el super_admin puede editar
$user->can('delete', $patient);  // false — ni el super_admin puede borrar
```

Esta prueba es la evidencia más importante del hito: confirma que la restricción del contrato se respeta incluso para el rol con más privilegios del sistema.

---

## 🐛 Parte E — El bug del BOM (segunda vez que lo cazamos)

### El síntoma

Al reescribir `PatientController.php` con PowerShell usando `-Encoding UTF8`, apareció este error:

```
Fatal error: Namespace declaration statement has to be the very first statement...
```

### La causa

`-Encoding UTF8` en PowerShell agrega automáticamente un **BOM** (Byte Order Mark) — un carácter invisible al principio del archivo que marca la codificación. PHP interpreta cualquier cosa antes de `<?php` como contenido a imprimir — exactamente el mismo tipo de bug que cazamos en el Hito 0 con el `[` de `config/sanctum.php`.

### La solución

Cambiar a `-Encoding ascii` (que nunca agrega BOM) cuando el contenido no tiene tildes ni "ñ", o usar `UTF8Encoding($false)` de .NET directamente cuando sí se necesitan caracteres especiales (como hicimos después con `validation.php`).

> 💡 **La lección que se repite:** cualquier herramienta que reescriba un archivo de texto puede introducir caracteres invisibles al principio. La defensa sigue siendo la misma que en el Hito 0: verificar con `php -l` inmediatamente después de cada escritura, nunca asumir que "se ve bien" es lo mismo que "está bien".

---

## 🌐 Parte F — Mensajes de validación en español (mejora para todo el proyecto)

### El problema

Laravel trae sus mensajes de validación **predefinidos en inglés**. Cuando tú escribes un mensaje a mano (como en el login), sale en el idioma que elijas. Pero cuando Laravel genera el mensaje automáticamente (con reglas como `required`, `unique`, `email`), usa su configuración de idioma — y el proyecto tenía `APP_LOCALE=en`.

### La solución en 2 pasos

**1. Cambiar la configuración regional:**
```
APP_LOCALE=es
APP_FALLBACK_LOCALE=es
APP_FAKER_LOCALE=es_CL
```

**2. Instalar las traducciones oficiales** (Laravel no las trae integradas, hay que agregarlas como paquete):
```powershell
composer require laravel-lang/common --dev
php artisan lang:add es
```

Esto generó `lang/es/validation.php`, con **todos** los mensajes de validación traducidos.

### El extra: traducir también los nombres de los campos

No bastaba con traducir la estructura de la frase — el mensaje decía *"El campo national id ya ha sido registrado"* (la frase en español, pero el nombre del campo seguía en inglés/snake_case). Agregamos entradas en la sección `'attributes'` del archivo de traducciones:

```php
'national_id' => 'identificacion nacional',
'birth_date' => 'fecha de nacimiento',
// ... etc
```

Resultado final: *"El campo identificacion nacional ya ha sido registrado."* — completamente en español, y esto aplica **automáticamente a cualquier validación futura** en todo el proyecto, sin tener que traducir nada a mano en cada controlador nuevo.

---

## 🎓 Las 4 lecciones grandes de este hito

### 1. Los requisitos del proyecto son la fuente de verdad, no la intuición
En vez de inventar campos "típicos" de un modelo Paciente, fuimos a `RF-016` y al contrato. El diseño quedó alineado con lo que el equipo (incluida Nicoll) ya había acordado.

### 2. Una regla de negocio puede cambiar la arquitectura completa
La decisión "nadie del personal puede modificar al paciente" no fue solo una Policy restrictiva — obligó a repensar quién crea el registro inicial, y eso llevó a diseñar el primer endpoint público del sistema.

### 3. Public + escritura = necesita protección extra
Un endpoint sin token que crea datos siempre necesita alguna defensa contra abuso. Rate limiting no es opcional ahí — es la única barrera que existe.

### 4. La codificación de archivos es una fuente de bugs recurrente, no un caso aislado
Ya van dos veces (el `[` de Sanctum, el BOM de PowerShell) que un carácter invisible rompe algo. Vale la pena mantener la costumbre de verificar con `php -l` después de cualquier escritura automatizada de archivos.

---

## 📊 Estado del proyecto tras este hito

| Hito | Qué construye | Estado |
|:---:|---|:---:|
| **0** | Saneamiento técnico + Swagger | ✅ |
| **A–E** | Módulo administrativo completo | ✅ |
| **1** | Modelo Paciente | ✅ |
| **Extra** | Mensajes de validación en español | ✅ |
| **2** | CTA (Código Temporal de Atención) | ⏭️ Siguiente |
| **3** | Sesión Médica | Pendiente |
| **4** | Middleware de sesión activa | Pendiente |

---

## ▶️ Lo que viene

Con el Paciente registrado y protegido según las reglas de tu contrato, el siguiente paso es el **Hito 2 — CTA (Código Temporal de Atención)**: el mecanismo que le permite a ese paciente, ya registrado, entrar al sistema para su atención sin necesitar una cuenta tradicional.

---

*Guía de estudio · Proyecto SeñaVida · Fase 4 · Hito 1*
