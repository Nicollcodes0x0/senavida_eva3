# 🎫 Guía de Estudio — Fase 4 · Hito 2
## CTA — Código Temporal de Atención

---

## 🎯 ¿Qué hicimos en este hito?

Construimos el mecanismo que permite a un paciente **ya registrado** (Hito 1) presentarse a recibir atención, identificándose ante Admisión mediante un código de texto — sin cuenta, sin contraseña.

Pero lo más valioso de este hito no fue el código en sí: fue **el proceso de corregir un error de diseño antes de escribir una sola línea**, gracias a hacer la pregunta correcta en el momento correcto.

---

## 🔄 Parte A — El giro de diseño que cambió todo

### El diseño inicial (equivocado)

La primera versión del plan decía: *"Admisión genera el CTA para el paciente que tiene en pantalla, y luego lo valida."*

### La pregunta que lo destapó

> *"¿No debería ser al revés, el paciente genera el código y admisión lo escanea? Porque si no, ¿cómo sabrá admisión quién es el paciente y sus datos?"*

Esta pregunta identificó una **contradicción lógica** en el diseño original: si Admisión necesitaba tener al paciente ya identificado en pantalla para generar o validar su código, **el código no cumplía ninguna función real** — la identificación ya habría ocurrido antes por otro medio.

### El diseño corregido

```
1. El paciente se registra en la app (Hito 1)
2. El paciente GENERA su propio código (sin ayuda de nadie)
3. Se presenta con ese código en Admisión
4. Admisión ESCRIBE el código, sin saber aún quién es
5. El sistema le REVELA la identidad del paciente
```

> 💡 **La lección más importante de este hito:** el propósito de una credencial de identificación es **revelar** una identidad, no confirmarla. Si ya conoces la identidad antes de usar la credencial, la credencial está de más. Vale la pena aplicar esta misma pregunta a cualquier mecanismo de autenticación que diseñes en el futuro: *"¿qué sabe el sistema ANTES de esta credencial, y qué le aporta la credencial que no supiera ya?"*

---

## 🔐 Parte B — La consecuencia técnica: bcrypt sin `patient_id`

### El problema que generó el giro de diseño

Si `validate()` solo recibe `{ code }` (sin saber de antemano el paciente), y el hash se guarda con **bcrypt** (como las contraseñas), aparece un problema técnico real:

> bcrypt genera un hash **distinto cada vez**, incluso para el mismo texto de entrada (por el "salt" aleatorio que incluye). Esto significa que **no se puede buscar directamente por hash** en la base de datos — hay que comparar uno por uno con `Hash::check()`.

### La solución: acotar la búsqueda

En vez de comparar contra **todos** los códigos del sistema, comparamos solo contra los códigos **activos del centro de salud del funcionario autenticado**:

```php
$query = TemporaryAccessCode::where('status', 'active');

if ($funcionario->role !== 'super_admin') {
    $query->where('health_center_id', $funcionario->health_center_id);
}

$candidates = $query->get();

foreach ($candidates as $candidate) {
    if ($candidate->matchesCode($data['code'])) {
        $matchingCode = $candidate;
        break;
    }
}
```

**Por qué es viable:** en cualquier momento, un mismo hospital tiene pocos pacientes con código activo esperando validación — comparar uno por uno es rápido. Y de paso, esto cumple **automáticamente** la regla de tu contrato de que el código debe validarse contra el centro del funcionario, sin necesidad de una verificación aparte.

---

## ⚖️ Parte C — La decisión sobre `failed_attempts`, evaluada con honestidad

### El hueco que se identificó después de construir

Una vez armado el controlador, surgió una pregunta clave: si el código **no coincide con nada**, ¿a qué registro le sumamos un intento fallido? La respuesta honesta fue: **a ninguno**, porque no sabemos cuál "intentaba" ser.

### Las dos opciones que se evaluaron

**Opción A — Cambiar el diseño de nuevo:** que `validate()` reciba también el `national_id` del paciente, permitiendo ubicar exactamente su CTA y aplicar el contador correctamente.

**Opción B — Mantener el diseño, cerrar el hueco con rate limiting:** limitar cuántos intentos de validación puede hacer una misma IP en un período corto, en vez de rastrear intentos por código individual.

### Por qué se descartó la Opción A

No solo por ser "más trabajo" — sino porque **reabría la misma contradicción que ya se había resuelto**: pedirle el RUT al paciente antes de validar el código vuelve a acercarse a "ya sé quién es antes de usar el código", el mismo problema del diseño original.

### La decisión final: Opción B

```php
$throttleKey = 'cta-validate:'.$request->ip();

if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
    // 429
}

RateLimiter::hit($throttleKey, 300);
```

Máximo 5 intentos de validación cada 5 minutos, por IP. Los campos `failed_attempts` y `max_attempts` quedan en la tabla, listos para un futuro refinamiento, pero la protección real contra fuerza bruta pasa por este límite.

> 💡 **Lección:** no toda debilidad detectada necesita resolverse rediseñando desde cero. A veces la solución correcta es una capa de protección **distinta** a la que originalmente se pensó, que cierra el mismo riesgo sin reintroducir el problema que ya se había resuelto.

---

## 🏗️ Parte D — Lo que se construyó

### El modelo, con lógica propia

`TemporaryAccessCode` no es solo una tabla — tiene 4 métodos que encapsulan su comportamiento:

```php
TemporaryAccessCode::generateCode();     // genera "SV-XXXXXX" con random_int()
$cta->matchesCode($plainCode);           // compara con Hash::check(), case-insensitive
$cta->isExpired();                       // ¿venció?
$cta->isBlocked();                       // ¿se agotaron los intentos?
```

**`random_int()` en vez de `rand()`:** es la función de PHP pensada para generar números **criptográficamente seguros** — importante porque este número funciona como credencial de seguridad, no es un detalle trivial.

### Una Policy con un método fuera de la convención estándar

```php
public function validateCode(User $user): bool
{
    return in_array($user->role, ['admision', 'super_admin']);
}
```

Todas las Policies anteriores usaban los 7 métodos estándar de Laravel (`view`, `create`, `update`, etc.). Aquí aprendiste que **puedes agregar cualquier método con el nombre que tenga sentido** para tu dominio — "validar un código" no es ninguna acción CRUD tradicional, así que se le puso su propio nombre: `validateCode`.

### Dos endpoints, dos niveles de protección

| Endpoint | Quién | Protección |
|---|---|---|
| `POST /patients/{id}/attention-codes` | El paciente | 🔓 Público + rate limiting |
| `POST /attention-codes/validate` | Admisión | 🔒 Token + Policy + rate limiting |

---

## 🧪 Lo que se probó en vivo

| Prueba | Resultado |
|---|---|
| Generar CTA sin token | `201`, código en claro devuelto una sola vez |
| Generar un segundo código para el mismo paciente | `201`, y el primero pasó a `expired` automáticamente |
| Validar con el código ya invalidado | `422` — comportamiento correcto, no un bug |
| Validar con el código vigente | `200`, revela correctamente al paciente |
| Rate limiting en generación (6º intento) | `429`, con el tiempo de espera calculado |

### Un momento importante de depuración

Al probar, apareció un `422` inesperado. En vez de asumir que era un bug, se investigó directamente en la base de datos con Tinker:

```php
$expiredCode->matchesCode('SV-195348');   // true
```

Esto confirmó que el código **sí era correcto en cuanto al hash**, pero pertenecía a un CTA que ya había pasado a `expired` — el sistema estaba funcionando exactamente como se diseñó (invalidando el código anterior al generar uno nuevo). No era un error del código, era una confusión sobre cuál código de prueba se estaba usando.

> 💡 **Lección:** antes de asumir que algo falló, verifica el estado real de los datos. Un resultado "inesperado" a veces es la regla de negocio funcionando correctamente, y lo que falló fue la expectativa de la prueba, no el sistema.

---

## 🎓 Las 4 lecciones grandes de este hito

### 1. La mejor revisión de diseño ocurre antes de escribir código
El error de fondo no se encontró probando el sistema — se encontró **conversando sobre el flujo** antes de tocar el teclado. Revisar el diseño con alguien más (o simplemente explicándolo en voz alta) sigue siendo una de las formas más baratas de encontrar errores.

### 2. Una credencial que no revela nada nuevo no es una credencial
Si el sistema ya sabe lo que la credencial debería decirle, esa credencial no está cumpliendo su función. Vale la pena aplicar esta pregunta a cualquier mecanismo de autenticación futuro.

### 3. No toda solución a un problema de seguridad es "más restricción en el mismo lugar"
Cuando apareció el hueco de `failed_attempts`, la respuesta correcta no fue "agreguemos más datos al endpoint" — fue reconocer que una capa de protección distinta (rate limiting) cerraba el mismo riesgo sin reabrir un problema ya resuelto.

### 4. Verificar el estado real de los datos evita perseguir bugs que no existen
El `422` "sospechoso" se resolvió en minutos consultando la base de datos directamente, en vez de revisar el código buscando un error que no estaba ahí.

---

## 📊 Estado del proyecto tras este hito

| Hito | Qué construye | Estado |
|:---:|---|:---:|
| **0** | Saneamiento técnico + Swagger | ✅ |
| **A–E** | Módulo administrativo completo | ✅ |
| **1** | Modelo Paciente | ✅ |
| **2** | CTA (generar + validar) | ✅ |
| **3** | Sesión Médica (consumir CTA + abrir atención) | ⏭️ Siguiente |
| **4** | Middleware de sesión activa | Pendiente |

---

## ▶️ Lo que viene

Con el paciente identificándose correctamente ante Admisión, el siguiente paso es el **Hito 3 — Sesión Médica**: el modelo que falta para poder **consumir** el CTA (el tercer endpoint que quedó pendiente, T2) y abrir formalmente la atención.

---

*Guía de estudio · Proyecto SeñaVida · Fase 4 · Hito 2*
