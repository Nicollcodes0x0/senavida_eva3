# 🩺 Informe de Evidencias — Backend SeñaVida

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13.23-FF2D20?style=flat-square&logo=laravel&logoColor=white" alt="Laravel 13"/>
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.4"/>
  <img src="https://img.shields.io/badge/PostgreSQL-336791?style=flat-square&logo=postgresql&logoColor=white" alt="PostgreSQL"/>
  <img src="https://img.shields.io/badge/Sanctum-Bearer%20Token-2E7D32?style=flat-square" alt="Sanctum"/>
  <img src="https://img.shields.io/badge/Swagger-OpenAPI%203.0-85EA2D?style=flat-square&logo=swagger&logoColor=black" alt="Swagger OpenAPI 3.0"/>
  <img src="https://img.shields.io/badge/Rúbrica-100%2F100-brightgreen?style=flat-square" alt="Rúbrica 100/100"/>
  <img src="https://img.shields.io/badge/Fase%205-COMPLETA-2E7D32?style=flat-square" alt="Fase 5 completa"/>
  <img src="https://img.shields.io/badge/Fase%206-COMPLETA-2E7D32?style=flat-square" alt="Fase 6 completa"/>
</p>

> Evidencia completa de funcionamiento del backend de **SeñaVida**, probada endpoint por endpoint con **Postman** y **Swagger UI**, y verificada a nivel de base de datos con **Tinker**. Este documento acompaña la entrega del **EVA2** y demuestra, con capturas reales (no simuladas), que el proyecto cumple cada indicador de la rúbrica.
>
> **Alcance ampliado.** Las secciones 1–5 corresponden a la entrega original del EVA2. Las secciones 6–13 documentan el trabajo posterior de la **Fase 4 — Paciente, CTA y Sesión Médica**, ya **completa**: documentación interactiva con Swagger, CRUD administrativo completo (Hitos A–E), el **Hito 1** (modelo de Paciente), el **Hito 2** (Código Temporal de Atención), el **Hito 3** (Sesión Médica) y el **Hito 4** (middleware de sesión activa, además del manejo unificado de errores construido junto a él).

| | |
|---|---|
| 👩‍💻 **Estudiante** | Greudy Inoa |
| 🎓 **Institución** | Instituto Profesional San Sebastián |
| 📦 **Proyecto** | SeñaVida — Backend API REST |
| 🔗 **Repositorio** | [`GreudyInoa/senavida-backend-eva2`](https://github.com/GreudyInoa/senavida-backend-eva2) |
| ⚙️ **Stack** | Laravel 13 · PHP 8.4 · PostgreSQL · Laravel Sanctum |
| 📅 **Entrega EVA2** | 17 de agosto de 2026 |
| 🔄 **Última actualización** | 28 de agosto de 2026 — Fase 6 **completa** (Hitos 6.0 a 6.4: Pictogramas, Usuarios, Configuración de seguridad, Auditoría, Estadísticas y exportación firmada) |

---

## 📑 Índice

**Entrega EVA2**

1. [¿Qué es este documento y por qué existe?](#1-qué-es-este-documento-y-por-qué-existe)
2. [Configuración del entorno y la base de datos](#2-configuración-del-entorno-y-la-base-de-datos)
3. [Evidencias de Autenticación](#3-evidencias-de-autenticación)
4. [Evidencias de Registro de Usuarios y Cifrado](#4-evidencias-de-registro-de-usuarios-y-cifrado)
5. [Evidencias de Catálogos](#5-evidencias-de-catálogos)
   - 5.1–5.3 Creación de Organización, Centros y Unidades
   - 5.4 Control de roles (RBAC) · 5.5–5.7 Listados · 5.8 Multitenancy

**Fase 4 — trabajo posterior**

6. [Documentación interactiva con Swagger](#6-documentación-interactiva-con-swagger)
   - 6.1 La interfaz · 6.2 Autenticación · 6.3 Grupos de endpoints
   - 6.4–6.6 Ejecución real desde el navegador
7. [CRUD administrativo completo](#7-crud-administrativo-completo)
   - 7.1 Usuarios · 7.2 Organizaciones · 7.3 Centros de salud · 7.4 Rutas inexistentes
8. [Hito 1 — Modelo de Paciente](#8-hito-1--modelo-de-paciente)
   - 8.1 Autorregistro · 8.2 Duplicados · 8.3 Consulta · 8.4 Por qué no hay `PUT` ni `DELETE`
9. [Hito 2 — Código Temporal de Atención (CTA)](#9-hito-2--código-temporal-de-atención-cta)
   - 9.1 Decisión de diseño · 9.2 Parámetros · 9.3–9.4 Generación
   - 9.5–9.6 Validación · 9.7 Fuerza bruta · 9.8 Pendiente
10. [Hito 3 — Sesión Médica](#10-hito-3--sesión-médica)
    - 10.1 Decisiones de diseño (D-07, T2/S1) · 10.2 Recorrido por rol
    - 10.3 S1 Abrir · 10.4 S3 Listar activas · 10.5 S4 Avanzar · 10.7 S5 Cerrar
    - 10.6 Salto de emergencia (D-23) · 10.8 Hueco de seguridad · 10.9 Middleware
    - 10.10 Investigación de un gap que no existía · 10.11 Verificación cruzada
11. [Manejo unificado de errores](#11-manejo-unificado-de-errores)
    - 11.1 El problema · 11.2 Los 4 tipos de excepción
    - 11.3 Código legible por máquina · 11.4 Corrección de seguridad
12. [Estado actual de la base de datos](#12-estado-actual-de-la-base-de-datos)
13. [Detalle técnico destacado del Hito 3](#13-detalle-técnico-destacado-del-hito-3)
    - 13.1 Índice único parcial · 13.2 Errores de implementación · 13.3 **Cierre de Fase 4**

**Fase 5 — en curso**

14. [Fase 5 — Chat y Consentimientos (completa)](#14-fase-5--chat-y-consentimientos-completa)
    - 14.1 Hito 5.0 — Acceso del paciente (token derivado del CTA)
    - 14.2 Hito 5.1 — Catálogo de Pictogramas
    - 14.3 Hito 5.2 — `ChatMessage`: el chat de la atención
    - 14.4 Hito 5.3 — Mensajes de sistema
    - 14.5 Hito 5.4 — `Consent`: el sistema de consentimientos
    - 14.6 Hito 5.5 — Cascada de cierre completa · 14.7 Estado de la fase

**Fase 6 — en curso**

15. [Fase 6 — Administración (en progreso)](#15-fase-6--administración-en-progreso)
    - 15.1 Hito 6.0 — CRUD completo de Pictogramas (5 bugs encontrados y corregidos)
    - 15.2 Hito 6.1 — Gestión de usuarios (corrección de escalación de privilegios crítica)
    - 15.3 Hito 6.2 — Parámetros de seguridad (`security_settings`), CTA conectado en tiempo real
    - 15.4 Hito 6.3 — Consulta de auditoría (`audit-logs`), severidad y aislamiento por centro
    - 15.5 Hito 6.4 — `/admin/stats` y exportación firmada (HMAC-SHA256) — **Fase 6 completa**

**Cierre**

16. [Cumplimiento de la rúbrica](#16-cumplimiento-de-la-rúbrica)
17. [Glosario rápido](#17-glosario-rápido)

---

## 1. ¿Qué es este documento y por qué existe?

Cuando se construye una API, es fácil demostrar que "funciona" con palabras — pero lo que realmente convence es **verla funcionar de verdad**, con datos reales entrando y saliendo del servidor. Eso es justo lo que hace este informe: por cada pieza importante del backend, muestra la **petición que se envió** y la **respuesta que devolvió el servidor**, tal como sucedió, sin inventar ni simular nada.

Piénsalo como el cuaderno de bitácora de un examen práctico de manejo: no basta con decir "sé estacionar en paralelo", hay que mostrarlo estacionando de verdad, con el examinador mirando. Cada captura de este documento es esa prueba: el examinador (en este caso, quien evalúa el EVA2) puede ver exactamente qué se envió y qué respondió el sistema, código HTTP incluido.

### ¿Cómo se probó todo esto?

Se usaron tres herramientas complementarias:

- **[Postman](https://www.postman.com/):** una aplicación que permite enviar peticiones HTTP (como si fuera un navegador, pero más flexible) directamente a los endpoints de la API, sin necesidad de tener un frontend construido todavía.
- **[Swagger UI](https://swagger.io/tools/swagger-ui/):** la documentación interactiva generada por el propio backend (sección 6), que permite leer y **ejecutar** cada endpoint desde el navegador. Las capturas de las secciones 6 a 9 provienen de aquí.
- **[Tinker](https://laravel.com/docs/artisan#tinker):** una consola interactiva que trae Laravel, donde se pueden ejecutar comandos de PHP directamente contra la base de datos real del proyecto. Se usó puntualmente para **verificar** que lo que Postman mostraba en pantalla realmente había quedado guardado (y correctamente cifrado) en PostgreSQL.

### La arquitectura en una frase

El backend es una **API REST versionada** (todas las rutas viven bajo `/api/v1`), que no devuelve páginas HTML sino **JSON puro**, protegida con **tokens Bearer de Laravel Sanctum** (el "carnet de identidad digital" que cada usuario recibe al hacer login y debe mostrar en cada petición siguiente), y con **PostgreSQL** como base de datos.

---

## 2. Configuración del entorno y la base de datos

> 💡 **¿Por qué empezar por aquí?** Antes de mostrar que un endpoint responde bien, hay que demostrar algo más básico: que el proyecto realmente está hablando con **PostgreSQL** y no con otra base de datos, o peor, con ninguna. Esta sección es la base de todo lo demás — si esto falla, nada más importa.

### 2.1 Variables de entorno (`.env`)

> **Qué demuestra:** que la conexión a PostgreSQL está correctamente configurada en las variables de entorno, tal como exige el Indicador 1 de la rúbrica.

El archivo `.env` es donde Laravel guarda toda la configuración que **depende del entorno** (local, producción, testing) y que **nunca debe subirse a GitHub** tal cual, porque contiene credenciales. Aquí es donde se le dice a Laravel: "conéctate a esta base de datos, en esta dirección, con este usuario y esta contraseña".

![Configuración del archivo .env](capturas/01_env.png)

La captura muestra el bloque de conexión a base de datos: `DB_CONNECTION=pgsql`, `DB_HOST=127.0.0.1`, `DB_PORT=5432`, `DB_DATABASE=senavida`, `DB_USERNAME=postgres`. Esto confirma que el proyecto está configurado para conectarse a **PostgreSQL**, tal como exige el enunciado.

> 🔒 **Buena práctica aplicada:** el valor real de `DB_PASSWORD` fue cubierto intencionalmente en la captura antes de subir este informe al repositorio público de GitHub — evitando exponer una credencial real, tal como haría cualquier desarrollador profesional.

### 2.2 Verificación de la conexión (`php artisan about`)

> **Qué demuestra:** que Laravel está conectado a PostgreSQL de verdad (no solo declarado en el `.env`, sino en ejecución).

`php artisan about` es un comando que muestra una "ficha técnica" completa del proyecto en el momento exacto en que se ejecuta: versión de Laravel, de PHP, y — lo más importante aquí — a qué motor de base de datos está conectado *ahora mismo*.

![php artisan about](capturas/02_artisan_about.png)

La salida del comando confirma la configuración real del proyecto en ejecución: `Laravel Version 13.23.0`, `PHP Version 8.4.24`, y en la sección **Drivers → Database: `pgsql`**. Esto prueba que la aplicación está efectivamente conectada a PostgreSQL, no solo declarado en el `.env`.

### 2.3 Migraciones ejecutadas (`php artisan migrate:status`)

> **Qué demuestra:** que todas las tablas del sistema se crearon correctamente.

Las **migraciones** son como planos de construcción para la base de datos: cada archivo de migración describe una tabla (sus columnas, tipos de dato, relaciones). `migrate:status` muestra cuáles de esos planos ya se "construyeron" de verdad en PostgreSQL.

![php artisan migrate:status](capturas/03_migrate_status.png)

Todas las migraciones del proyecto figuran en estado **`Ran`**, incluyendo `create_users_table`, `create_personal_access_tokens_table` (Sanctum), `create_audit_logs_table`, `create_organizations_table`, `create_health_centers_table`, `create_units_table` y `add_foreign_keys_to_users_table`. Esto confirma que todas las tablas del sistema fueron creadas correctamente en PostgreSQL.

---

## 3. Evidencias de Autenticación

> 💡 **¿Qué se está probando aquí?** La autenticación es el "portero" del sistema: decide quién entra y quién no. Un buen sistema de login no solo debe **aceptar** las credenciales correctas — también debe **rechazar** las incorrectas, **protegerse** contra quien intente adivinar contraseñas a la fuerza, y **saber revocar el acceso** cuando alguien cierra sesión. Esta sección prueba las cinco caras de esa moneda.

El siguiente diagrama resume el flujo completo, de principio a fin, antes de entrar al detalle de cada endpoint:

![Flujo de autenticación en SeñaVida](capturas/00_flujo_autenticacion.png)

### 3.1 Login exitoso

> **Endpoint:** `POST /api/v1/auth/login`
> **Qué demuestra:** que un usuario con credenciales correctas recibe un token Sanctum válido.

**Petición enviada:**
```json
{
  "email": "medico.maternidad@test.com",
  "password": "password123"
}
```

![Login exitoso en Postman](capturas/04_login_exitoso.png)

**Resultado obtenido:** `200 OK` en `635 ms`. La respuesta incluye `"success": true` y dentro de `"data"`: el `token` de Sanctum (cuyo valor se omite aquí por seguridad), el `tokenType: "Bearer"`, y los datos del usuario autenticado (`id`, `name: "Dr. Maternidad"`, `email`, `role: "medico"`, `isActive: true`).

Esto confirma que el login **valida las credenciales de verdad** y entrega un token Bearer real de Sanctum, listo para usarse en los siguientes endpoints protegidos.

---

### 3.2 Login fallido (credenciales inválidas)

> **Endpoint:** `POST /api/v1/auth/login`
> **Qué demuestra:** que el sistema rechaza credenciales incorrectas y no entrega token. Esto prueba que el login **valida de verdad** (no acepta cualquier cosa).

![Login fallido en Postman](capturas/05_login_fallido.png)

**Resultado obtenido:** `422 Unprocessable Content` en `1.62 s`. La respuesta indica `"message": "Las credenciales no son correctas."`, con el detalle del error en `"errors" → "email"`.

Esto confirma que el endpoint **rechaza credenciales incorrectas** y no entrega ningún token, cerrando la puerta a accesos no autorizados.

---

### 3.3 Protección contra fuerza bruta (rate limiting)

> **Endpoint:** `POST /api/v1/auth/login`
> **Qué demuestra:** que tras varios intentos fallidos seguidos, el sistema bloquea temporalmente los intentos (protección de seguridad profesional).

**Respuesta esperada:** al 6.º intento fallido, `429 Too Many Requests` con el mensaje de segundos de espera.

![Rate limiting activado - 429](capturas/06_rate_limiting_429.png)

**Resultado obtenido:** tras 5 intentos fallidos consecutivos con credenciales incorrectas, el 6.º intento devolvió `429 Too Many Requests` en `265 ms`, con el mensaje `"Demasiados intentos. Intenta de nuevo en 16 segundos."`.

Esto confirma que el `RateLimiter` de Laravel está protegiendo activamente el endpoint de login contra ataques de fuerza bruta, bloqueando temporalmente el email/IP tras superar el máximo de intentos permitidos.

---

### 3.4 Endpoint `/me` (usuario autenticado)

> **Endpoint:** `GET /api/v1/auth/me`
> **Qué demuestra:** que un token válido permite identificar al usuario dueño de la sesión. Prueba que el **middleware de autenticación** (`auth:sanctum`) funciona.

**Configuración:** en Postman, pestaña **Authorization → Bearer Token**, pegar el token obtenido en el login (copiado directamente desde la respuesta, nunca transcrito a mano).

![Endpoint /me con token válido](capturas/07_me_con_token.png)

**Resultado obtenido:** `200 OK` en `371 ms`. La respuesta devuelve `"success": true` y dentro de `"data" → "user"` los datos del usuario autenticado: `id`, `name: "Super Admin"`, `email: "superadmin@test.com"`, `role: "super_admin"`, `isActive: true`.

Esto confirma que el middleware `auth:sanctum` valida correctamente el token Bearer y resuelve la identidad del usuario dueño de la sesión.

---

### 3.5 Logout (revocación del token)

> **Endpoint:** `POST /api/v1/auth/logout`
> **Qué demuestra:** que el cierre de sesión **revoca el token**, de modo que ya no sirve para nada después.

**Respuesta esperada:** `200 OK` confirmando el cierre de sesión.

![Logout exitoso](capturas/08_logout.png)

**Resultado obtenido:** `200 OK` en `356 ms`, con `"success": true`. Esto confirma que la petición de logout se procesó correctamente sobre el token activo del `super_admin`.

**Prueba complementaria (opcional pero potente):** volver a llamar a un endpoint protegido con el **mismo token** después del logout → debe devolver `401 Unauthorized`.

![Token revocado tras logout](capturas/09_token_revocado.png)

**Resultado obtenido:** al reutilizar el mismo token (`11|...`) después de haber cerrado sesión con él, la API devuelve `401 Unauthorized` con `"message": "No autenticado."`.

Esto demuestra que el logout **revoca el token de verdad** en la base de datos — no es solo una respuesta de cortesía en el frontend; el token queda inservible para cualquier petición posterior.

---

## 4. Evidencias de Registro de Usuarios y Cifrado

> 💡 **¿Por qué esta sección es tan importante?** Guardar una contraseña **tal como el usuario la escribió** (en "texto plano") es uno de los errores de seguridad más graves que puede cometer un sistema — si alguien accede a la base de datos, tendría todas las contraseñas reales. Por eso, ningún backend serio guarda contraseñas así: las **cifra** con un algoritmo que las convierte en un texto irreversible (un *hash*). Esta sección demuestra, con evidencia directa en la base de datos, que SeñaVida hace esto correctamente. Es, además, el **Indicador 3** completo de la rúbrica.

### 4.1 Registro de usuario exitoso

> **Endpoint:** `POST /api/v1/users`
> **Qué demuestra:** que un administrador autenticado puede crear un usuario nuevo, con todas las validaciones.

**Petición enviada:**
```json
{
  "name": "Enfermera de Prueba",
  "email": "enfermera.prueba@test.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "categorizacion",
  "organizationId": "01a003c6-e068-71b0-8165-7657c0a84b44",
  "healthCenterId": "01a003c8-20e8-7095-b24b-08918e04f79a",
  "unitId": "01a003c9-6a18-73c6-bc23-70b80f0395fa"
}
```

![Registro de usuario exitoso](capturas/10_registro_usuario.png)

**Resultado obtenido:** `201 Created` en `1.38 s`. La respuesta devuelve `"success": true` y los datos del usuario creado (`id`, `name: "Enfermera de Prueba"`, `email`, `role: "categorizacion"`, `isActive: true`) — **sin incluir la contraseña en ningún formato**, ni siquiera cifrada.

Esto confirma que el endpoint crea correctamente un usuario nuevo, asociado al Hospital San Rafael → Urgencia Adulto, y que la respuesta respeta la buena práctica de nunca exponer la contraseña.

---

### 4.2 Prueba del cifrado en la base de datos (Tinker) ⭐

> **Qué demuestra:** que la contraseña **NO se guarda en texto plano**, sino cifrada con bcrypt. **Esta es la evidencia clave del cifrado.**

**Comando ejecutado en Tinker:**
```php
User::where('email', 'enfermera.prueba@test.com')->first()->password;
```

![Hash bcrypt verificado en Tinker](capturas/11_hash_bcrypt_tinker.png)

**Resultado obtenido:**
```
$2y$12$fU2Ak7sfTq/Luog9hBjr.etaHlRrAkg9ctKgLCn2Wdo9DoSiAh3zy
```

> **Explicación:** aunque en Postman se envió la contraseña `password123` en texto plano, en la base de datos quedó guardada como un hash irreversible en formato **bcrypt** (`$2y$12$...`, donde `12` es el número de rondas de cifrado configurado en `BCRYPT_ROUNDS`). Esto se logra con el cast `'password' => 'hashed'` en el modelo `User`, que cifra automáticamente la contraseña al guardarla — sin necesidad de llamar a `Hash::make()` manualmente en el controlador.

---

### 4.3 Validación de datos duplicados

> **Endpoint:** `POST /api/v1/users`
> **Qué demuestra:** que el sistema rechaza crear un usuario con un email que ya existe (regla `unique`).

**Respuesta esperada:** `422 Unprocessable Content` con el mensaje de que el email ya está en uso.

![Registro duplicado rechazado](capturas/12_registro_duplicado.png)

**Resultado obtenido:** `422 Unprocessable Content` en `424 ms`, con el mensaje `"The email has already been taken."`. La misma petición del punto 4.1, repetida sin cambios, fue rechazada por la regla de validación `unique` sobre el campo `email`.

Esto confirma que el sistema **evita duplicados a nivel de servidor**, no solo a nivel de interfaz — cualquier intento de crear dos cuentas con el mismo correo es bloqueado.

---

## 5. Evidencias de Catálogos

> 💡 **¿Qué son los "catálogos" en este proyecto?** SeñaVida no atiende a un solo hospital: está pensado para que **varias organizaciones de salud** (por ejemplo, distintos servicios de salud regionales) usen el mismo sistema, cada una con sus propios hospitales (**centros de salud**) y, dentro de cada hospital, sus propias salas (**unidades**, como "Urgencia Adulto" o "Maternidad"). Estas tres entidades forman una jerarquía: `Organización → Centro de Salud → Unidad`. Esta sección prueba que esa jerarquía se puede crear y consultar correctamente por la API — y de paso, revela dos capas de seguridad extra que no eran obligatorias, pero que hacen al sistema más robusto: control de roles y aislamiento de datos entre hospitales.

El siguiente diagrama resume visualmente esa jerarquía, con datos reales del proyecto (los mismos que se crean y consultan en esta sección):

![Estructura jerárquica del sistema SeñaVida](capturas/00_estructura_sistema.jpeg)

### 5.1 Crear Organización

> **Endpoint:** `POST /api/v1/organizations`
> **Qué demuestra:** que un `super_admin` puede dar de alta una nueva organización de salud desde cero.

**Petición enviada:**
```json
{
  "name": "Servicio de Salud Metropolitano"
}
```

![Creación de organización](capturas/13_crear_organizacion.png)

**Resultado obtenido:** `201 Created` en `908 ms`, devolviendo el `id` (UUID) generado y el `name` de la organización creada.

---

### 5.2 Crear Centro de Salud

> **Endpoint:** `POST /api/v1/health-centers`
> **Qué demuestra:** que un centro de salud se crea vinculado a su organización mediante `organizationId`.

**Petición 1 — "Hospital San Rafael":**
```json
{
  "name": "Hospital San Rafael",
  "organizationId": "01a003c6-e068-71b0-8165-7657c0a84b44"
}
```

![Creación del primer centro de salud](capturas/14_crear_centro_1.png)

**Resultado obtenido:** `201 Created` en `863 ms`.

**Petición 2 — "Hospital Santa Lucía" (segundo centro, misma organización):**
```json
{
  "name": "Hospital Santa Lucía",
  "organizationId": "01a003c6-e068-71b0-8165-7657c0a84b44"
}
```

![Creación del segundo centro de salud](capturas/15_crear_centro_2.png)

**Resultado obtenido:** `201 Created` en `723 ms`. Ambos centros quedan asociados a la misma `organizationId`, confirmando que una organización puede tener múltiples centros de salud (relación `hasMany`).

---

### 5.3 Crear Unidad

> **Endpoint:** `POST /api/v1/units`
> **Qué demuestra:** que una unidad se crea vinculada a su centro de salud mediante `healthCenterId`.

**Petición 1 — "Urgencia Adulto":**
```json
{
  "name": "Urgencia Adulto",
  "healthCenterId": "01a003c8-20e8-7095-b24b-08918e04f79a"
}
```

![Creación de la unidad Urgencia Adulto](capturas/16_crear_unidad_1.png)

**Resultado obtenido:** `201 Created` en `679 ms`.

**Petición 2 — "Urgencia Infantil" (segunda unidad, mismo centro):**
```json
{
  "name": "Urgencia Infantil",
  "healthCenterId": "01a003c8-20e8-7095-b24b-08918e04f79a"
}
```

![Creación de la unidad Urgencia Infantil](capturas/17_crear_unidad_2.png)

**Resultado obtenido:** `201 Created` en `752 ms`. Ambas unidades quedan asociadas al mismo `healthCenterId`, confirmando que un centro de salud puede tener múltiples unidades (relación `hasMany`).

> **Nota:** de la misma forma se crearon el resto de las unidades del sistema: "Maternidad" para el Hospital San Rafael, y "Urgencia Adulto", "Traumatología", "Pediatría" para el Hospital Santa Lucía, completando el catálogo que se muestra en el listado de la sección 5.7.
>
> ![Ejemplo de otra unidad creada (Pediatría)](capturas/24_unidades_completas.png)

---

### 5.4 Evidencia adicional — Control de roles (RBAC) en el registro de usuarios

> Esta evidencia complementa la sección 4: confirma que **solo los roles autorizados** (`admin_institucional`, `super_admin`) pueden crear usuarios nuevos — cualquier otro rol autenticado es rechazado, incluso si su token es válido.

El siguiente diagrama resume la regla que se prueba a continuación: quién puede crear qué dentro del sistema.

![Quién crea qué en SeñaVida](capturas/00_quien_crea_que.jpeg)

**Caso permitido — usuario con rol autorizado:**

![Registro permitido con rol autorizado](capturas/25_rbac_permitido.png)

**Resultado obtenido:** `201 Created` en `1.26 s`. El usuario "Enfermero Uno SR" se registró correctamente porque quien hizo la petición tenía un rol con permiso para crear usuarios.

**Caso bloqueado — usuario con rol sin autorización:**

![Registro bloqueado por falta de permisos](capturas/26_rbac_bloqueado.png)

**Resultado obtenido:** `403 Forbidden` en `766 ms`, con el mensaje `"No tienes permiso para registrar usuarios."`, usando la misma petición pero un token de un rol sin autorización para esta acción.

Esto confirma que el control de acceso por rol (RBAC) está aplicado **a nivel de servidor**, no solo ocultando botones en el frontend — un token válido no es suficiente por sí solo; el rol del usuario también se verifica en cada operación sensible.

---

### 5.5 Listado de Organizaciones

> **Endpoint:** `GET /api/v1/organizations`
> **Qué demuestra:** que las organizaciones creadas en el punto 5.1 se persisten correctamente en PostgreSQL y se pueden consultar vía API.

![Listado de organizaciones](capturas/18_listar_organizaciones.png)

**Resultado obtenido:** `200 OK` en `736 ms`, con `"success": true` y un arreglo `"data"` que incluye las organizaciones registradas.

---

### 5.6 Listado de Centros de Salud

> **Endpoint:** `GET /api/v1/health-centers`
> **Qué demuestra:** que los centros de salud creados en el punto 5.2 están correctamente vinculados a su organización.

![Listado de centros de salud](capturas/19_listar_centros.png)

**Resultado obtenido:** `200 OK` en `691 ms`. Cada centro ("Hospital San Rafael", "Hospital Clínico Sur") muestra su `organizationId` correspondiente, confirmando la relación `belongsTo` entre `HealthCenter` y `Organization`.

---

### 5.7 Listado de Unidades

> **Endpoint:** `GET /api/v1/units` — con y sin filtro por centro de salud
> **Qué demuestra:** que las unidades creadas en el punto 5.3 están correctamente vinculadas a su centro de salud, y que el endpoint soporta filtrado por `healthCenterId`.

**Sin filtro (todas las unidades):**

![Listado completo de unidades](capturas/21_listar_unidades_todas.png)

**Resultado obtenido:** `200 OK` en `732 ms`, con todas las unidades del sistema y su `healthCenterId` respectivo.

**Con filtro por centro de salud (`?healthCenterId=...`):**

![Listado de unidades filtrado por centro](capturas/20_listar_unidades_filtro.png)

**Resultado obtenido:** `200 OK` en `726 ms`, devolviendo únicamente las unidades del centro solicitado ("Urgencia Adulto", "Urgencia Infantil", "Maternidad"), confirmando que el filtro por query param funciona correctamente.

---

### 5.8 Evidencia adicional — Aislamiento por multitenancy

> Esta evidencia no estaba en el plan original, pero refuerza un aspecto de seguridad importante del sistema: que un `admin_institucional` **solo puede gestionar usuarios de su propio centro de salud**, nunca de otro.

**Caso permitido — registrar en el propio centro:**

![Registro permitido dentro del propio centro](capturas/22_multitenancy_permitido.png)

**Resultado obtenido:** `201 Created`. El usuario `admin_institucional` registró exitosamente a "Prueba Mismo Centro" porque el `healthCenterId` enviado coincide con el centro al que pertenece.

**Caso bloqueado — intento de registrar en otro centro:**

![Registro bloqueado fuera del propio centro](capturas/23_multitenancy_bloqueado.png)

**Resultado obtenido:** `403 Forbidden` en `1.05 s`, con el mensaje `"Solo puedes registrar usuarios en tu propio centro de salud."`. El mismo `admin_institucional` intentó registrar un usuario en un centro de salud distinto al suyo, y el sistema lo rechazó.

Esto confirma que la restricción de **multitenancy** no es solo una regla de negocio documentada, sino que está **implementada y forzada activamente en el servidor**, evitando que un administrador institucional gestione datos fuera de su propio centro.

---

## 6. Documentación interactiva con Swagger

> 💡 **¿Qué problema resuelve esta sección?** Hasta aquí, cada endpoint se probó con Postman — una herramienta que vive en el computador de quien desarrolla. Pero el backend de SeñaVida no lo consume solo su autora: lo consume **el frontend**, construido en paralelo por otra persona. Sin una documentación viva, cada endpoint nuevo obligaría a escribir un mensaje explicando qué recibe, qué devuelve y con qué errores puede fallar.
>
> **Swagger (OpenAPI)** resuelve exactamente eso: genera una página web, a partir del propio código del backend, donde **cada endpoint se puede leer y ejecutar desde el navegador**. Es documentación que no se desactualiza, porque nace del código y no de un documento aparte.

### 6.1 La interfaz generada

> **URL:** `http://127.0.0.1:8000/api/documentation`
> **Qué demuestra:** que la documentación OpenAPI 3.0 se genera correctamente y describe la API real del proyecto.

![Portada de Swagger UI](capturas/27_swagger_portada.png)

**Resultado obtenido:** la interfaz carga con el título **SenaVida API**, versión `1.0.0`, especificación **OAS 3.0**, y el servidor base correctamente apuntado a `http://127.0.0.1:8000/api/v1`. Debajo aparece el buscador por etiquetas y el primer grupo de endpoints.

> **Detalle técnico:** esta página no se escribió a mano. Se genera con el paquete `darkaonline/l5-swagger` a partir de **atributos nativos de PHP 8** (`#[OA\Get(...)]`, `#[OA\Post(...)]`) escritos directamente sobre cada método de cada controlador. Al ejecutar `php artisan l5-swagger:generate`, el paquete recorre el código y produce el archivo `api-docs.json` que alimenta esta interfaz.

### 6.2 Autenticación desde la propia documentación

> **Qué demuestra:** que Swagger reconoce el esquema de seguridad Bearer y permite autenticarse para probar endpoints protegidos.

![Modal de autorización de Swagger](capturas/28_swagger_authorize.png)

**Resultado obtenido:** el botón **Authorize** abre el cuadro `Available authorizations`, con el esquema `bearerAuth (http, Bearer)` y la descripción *"Token Bearer obtenido en /auth/login"*.

Al pegar aquí un token válido, **todas las peticiones posteriores lo incluyen automáticamente** en el header `Authorization`. Esto convierte a Swagger en un banco de pruebas completo: se puede recorrer la API entera sin salir del navegador ni configurar nada externo.

### 6.3 Los cuatro grupos de endpoints

Los endpoints están organizados por etiquetas (`tags`), que agrupan las operaciones por área funcional:

**Autenticación:**

![Grupo Autenticacion en Swagger](capturas/29_swagger_grupo_autenticacion.png)

Tres operaciones: `POST /auth/login` (iniciar sesión), `GET /auth/me` (obtener usuario autenticado) y `POST /auth/logout` (cerrar sesión). Nótese el **candado** junto a los dos últimos: Swagger marca visualmente cuáles exigen token.

**Catálogos:**

![Grupo Catalogos en Swagger](capturas/30_swagger_grupo_catalogos.png)

Las operaciones de lectura y creación sobre las tres entidades de la jerarquía institucional: `/health-centers`, `/organizations` y `/units`.

**Usuarios:**

![Grupo Usuarios en Swagger](capturas/31_swagger_grupo_usuarios.png)

El registro de usuarios del personal de salud, `POST /users`, también protegido con token.

### 6.4 Ejecución real desde Swagger — Autenticación

Lo importante de Swagger no es que *describa* los endpoints, sino que permita **ejecutarlos de verdad**. Las siguientes capturas muestran el ciclo completo de autenticación corrido íntegramente desde el navegador.

**Login:**

![Login ejecutado desde Swagger](capturas/32_swagger_login_ejecutado.png)

**Resultado obtenido:** `200 OK`. La respuesta entrega el `token` de Sanctum, el `tokenType: "Bearer"` y los datos del usuario `Super Admin` con rol `super_admin`.

> 🔒 **Buena práctica aplicada:** el valor del token fue **cubierto intencionalmente** en todas las capturas de este informe antes de subirlas al repositorio público. Un token Bearer es una credencial activa: publicarlo equivale a publicar una contraseña. Este mismo criterio se aplicó antes al valor de `DB_PASSWORD` en la sección 2.1.

**Usuario autenticado (`/auth/me`):**

![GET /auth/me ejecutado desde Swagger](capturas/33_swagger_me_ejecutado.png)

**Resultado obtenido:** `200 OK` devolviendo la identidad del usuario dueño del token — confirmando que el token pegado en `Authorize` viaja correctamente en cada petición.

**Logout:**

![Logout ejecutado desde Swagger](capturas/34_swagger_logout_ejecutado.png)

**Resultado obtenido:** `200 OK` con `{"success": true}`. El token queda revocado en el servidor de inmediato.

### 6.5 Ejecución real desde Swagger — Creación de catálogos

Para verificar que Swagger no solo sirve para leer sino también para **escribir**, se recreó la jerarquía institucional completa desde la propia documentación:

**Organización:**

![Crear organización desde Swagger](capturas/35_swagger_crear_organizacion.png)

`201 Created` — se creó `"Servicio de Salud Prueba Swagger"`.

**Centro de salud:**

![Crear centro de salud desde Swagger](capturas/36_swagger_crear_centro.png)

`201 Created` — se creó `"Hospital Prueba Swagger"`, vinculado por `organizationId` a la organización anterior.

**Unidad:**

![Crear unidad desde Swagger](capturas/37_swagger_crear_unidad.png)

`201 Created` — se creó `"Unidad Prueba Swagger"`, vinculada por `healthCenterId` al centro anterior.

**Usuario:**

![Crear usuario desde Swagger](capturas/38_swagger_crear_usuario.png)

`201 Created` — se creó `"Usuario Prueba Swagger"` con rol `admision`, asociado a la organización, centro y unidad recién creados. La contraseña **no aparece en la respuesta**, ni en texto plano ni cifrada.

> **Lo que demuestra esta secuencia:** los cuatro niveles del sistema (`Organización → Centro → Unidad → Usuario`) se pueden construir de punta a punta desde la documentación, respetando las relaciones entre ellos. Cada `id` devuelto alimenta la petición siguiente.

### 6.6 Ejecución real desde Swagger — Listados

**Organizaciones:**

![Listar organizaciones desde Swagger](capturas/39_swagger_listar_organizaciones.png)

**Centros de salud:**

![Listar centros desde Swagger](capturas/40_swagger_listar_centros.png)

`200 OK` con `"Hospital San Rafael"` y `"Hospital Santa Lucía"`, cada uno con su `organizationId`.

**Unidades — sin filtro:**

![Listar todas las unidades desde Swagger](capturas/41_swagger_listar_unidades.png)

`200 OK` con las unidades de todos los centros: `Urgencia Adulto`, `Urgencia Infantil`, `Maternidad`, `Traumatología`.

**Unidades — filtradas por centro (`?healthCenterId=`):**

![Listar unidades filtradas desde Swagger](capturas/42_swagger_listar_unidades_filtro.png)

`200 OK` devolviendo **únicamente** las tres unidades del Hospital San Rafael. El filtro por *query parameter* funciona correctamente, y Swagger lo documenta como parámetro opcional.

---

## 7. CRUD administrativo completo

> 💡 **¿Qué falta cuando solo se puede crear y listar?** Las secciones anteriores demostraron que se pueden **crear** y **consultar** organizaciones, centros, unidades y usuarios. Pero un sistema real necesita también **corregir** datos mal cargados y **retirar de circulación** entidades que ya no operan. Esta sección documenta esas dos operaciones faltantes.
>
> Hay una decisión de diseño importante detrás: **SeñaVida nunca borra registros de verdad**. Al "eliminar" una entidad, lo que hace es marcarla como inactiva (`isActive: false`) — un patrón llamado **borrado lógico** (*soft delete*). En un sistema de salud esto no es opcional: un hospital dado de baja sigue teniendo atenciones históricas asociadas, y borrarlo físicamente destruiría trazabilidad clínica.

### 7.1 CRUD de Usuarios

**Listar todos los usuarios:**

![Listado de usuarios](capturas/43_users_listar.png)

`200 OK` con el listado completo, mostrando para cada usuario su `role` y su ubicación en la jerarquía (`organizationId`, `healthCenterId`, `unitId`). Nótese que el `super_admin` tiene los tres campos en `null`: no pertenece a ningún centro concreto porque su alcance es global.

**Ver un usuario específico:**

![Detalle de un usuario](capturas/44_users_ver.png)

`200 OK` con la ficha completa del usuario `"Usuario Prueba Swagger"`, rol `admision`.

**Editar un usuario:**

![Edición de usuario](capturas/45_users_editar.png)

`200 OK` — el `PUT` actualizó el nombre a `"Usuario Editado desde Swagger"`. La respuesta devuelve el registro ya modificado.

**Desactivar un usuario:**

![Desactivación de usuario](capturas/46_users_desactivar.png)

`200 OK` con `{"id": ..., "isActive": false}`. El `DELETE` **no eliminó la fila**: cambió su estado.

**Verificar la desactivación:**

![Verificación del usuario desactivado](capturas/47_users_verificar_desactivado.png)

`200 OK` — el registro **sigue existiendo y sigue siendo consultable**, ahora con `isActive: false`. Esta captura es la prueba directa de que el borrado es lógico y no físico: si hubiera sido un `DELETE` real, esta consulta habría devuelto `404`.

### 7.2 CRUD de Organizaciones

El mismo ciclo aplicado al nivel más alto de la jerarquía:

**Listar:**

![Listado de organizaciones](capturas/48_orgs_listar.png)

`200 OK` con las dos organizaciones del sistema, ambas con `isActive: true`.

**Ver una organización:**

![Detalle de organización](capturas/49_orgs_ver.png)

**Editar:**

![Edición de organización](capturas/50_orgs_editar.png)

`200 OK` — el nombre pasó a `"Servicio de Salud Metropolitano Editado"`.

**Desactivar:**

![Desactivación de organización](capturas/51_orgs_desactivar.png)

`200 OK` con `isActive: false`.

**Verificar:**

![Verificación de organización desactivada](capturas/52_orgs_verificar_desactivado.png)

`200 OK` — el registro persiste con su nombre editado y su estado inactivo.

### 7.3 CRUD de Centros de Salud

**Listar:**

![Listado de centros de salud](capturas/53_centros_listar.png)

`200 OK` con los tres centros del sistema y su estado.

**Ver un centro:**

![Detalle de centro de salud](capturas/54_centros_ver.png)

`200 OK` con los datos del `"Hospital San Rafael"`. Debajo de la respuesta, Swagger muestra la **tabla de códigos documentados** para este endpoint: `200` (datos del centro), `401` (no autenticado), `403` (sin permiso) y `404` (centro no encontrado).

> **Por qué importa esa tabla:** documentar los errores posibles es tan valioso como documentar el caso exitoso. El frontend necesita saber de antemano qué puede recibir para manejar cada caso sin adivinar.

**Editar:**

![Edición de centro de salud](capturas/55_centros_editar.png)

`200 OK` — el nombre pasó a `"Hospital San Lupe Actualizado"`.

**Desactivar:**

![Desactivación de centro de salud](capturas/56_centros_desactivar.png)

`200 OK` con `isActive: false`.

**Verificar:**

![Verificación de centro desactivado](capturas/57_centros_verificar_desactivado.png)

`200 OK` — el centro sigue consultable, con su nombre editado y desactivado.

### 7.4 Manejo de rutas inexistentes

> **Qué demuestra:** cómo responde el framework cuando la ruta solicitada no está registrada.

![Error 404 por ruta no encontrada](capturas/58_centros_404_no_encontrado.png)

**Resultado obtenido:** `404 Not Found` con el mensaje `"The route api/v1/health-centers/{id} could not be found."` y la excepción `Symfony\Component\HttpKernel\Exception\NotFoundHttpException`.

Esta captura se tomó **durante la construcción** del endpoint de detalle, antes de registrar la ruta — y conviene distinguir dos tipos de `404` que se ven parecidos pero significan cosas distintas:

| Tipo | Significado | Cuándo ocurre |
|---|---|---|
| **Ruta no encontrada** | La URL no corresponde a ningún endpoint registrado | El endpoint no existe o está mal escrito |
| **Recurso no encontrado** | El endpoint existe, pero el `id` solicitado no está en la base de datos | Se pide una entidad que no existe (o de otro centro, por aislamiento) |

El primero es lo que muestra esta captura; el segundo es el `404` documentado en la tabla de la sección 7.3. La captura 54 confirma que, una vez registrada la ruta, el mismo `id` devuelve `200` correctamente.

> ⚠️ **Nota de seguridad:** la respuesta incluye la traza completa del error con rutas absolutas del servidor (`C:\laragon\www\...`). Esto ocurre porque el entorno local tiene `APP_DEBUG=true`. **En producción, `APP_DEBUG` debe estar en `false`**, y Laravel entrega entonces un mensaje genérico sin exponer estructura interna ni rutas del sistema de archivos — información que un atacante podría aprovechar.

---

## 8. Hito 1 — Modelo de Paciente

> 💡 **El cambio de perspectiva de esta sección.** Todo lo anterior gira en torno al **personal de salud**: usuarios que inician sesión, con roles y permisos. Aquí entra el otro protagonista del sistema, y funciona con reglas completamente distintas.
>
> El paciente de SeñaVida es una **persona sorda que llega a urgencias**. No tiene cuenta, no tiene contraseña, y probablemente está en una situación de estrés donde crear credenciales sería un obstáculo inaceptable. Por eso el registro del paciente es un **endpoint público**: cualquiera puede completarlo desde su propio teléfono, sin token, sin login.

### 8.1 Autorregistro del paciente

> **Endpoint:** `POST /api/v1/patients` — 🔓 **público, sin autenticación**
> **Qué demuestra:** que un paciente puede registrarse por sí mismo, sin intervención de personal ni credenciales.

![Autorregistro de paciente](capturas/59_paciente_autorregistro.png)

**Resultado obtenido:** `201 Created`. Obsérvese en el comando `curl` que **no existe header `Authorization`** — la petición no lleva token de ningún tipo, y aun así el servidor la acepta.

Los datos enviados incluyen identificación (`national_id`, `national_id_type`), fecha de nacimiento, previsión de salud, dirección, teléfono, CESFAM de origen, alergias, condiciones de salud y — el campo más importante para la misión del proyecto — la **preferencia de comunicación** (`texto`, `senas`, `mixto`).

**Detalle destacable de la respuesta:**

```json
{
  "id": "01a024de-7763-71df-8bb4-42495f3c999c",
  "name": "Juan Soto",
  "nationalId": "98765432-1",
  "age": 36,
  "communicationPreference": "texto",
  "isActive": true
}
```

El campo `age` **no se envió en la petición**. Se calcula en el servidor a partir de `birth_date` mediante un *accessor* del modelo que usa Carbon. Esto evita un problema clásico: si la edad se guardara como un número fijo, quedaría desactualizada al día siguiente del cumpleaños del paciente. Guardando la fecha de nacimiento y derivando la edad al momento de consultarla, el dato **siempre es correcto**.

### 8.2 Control de duplicados

> **Qué demuestra:** que el sistema impide registrar dos veces a la misma persona.

![Registro de paciente duplicado rechazado](capturas/60_paciente_duplicado_422.png)

**Resultado obtenido:** `422 Unprocessable Content` con el mensaje `"The national id has already been taken."`.

La petición usó **el mismo `national_id`** (`98765432-1`) que el registro anterior, pero con un nombre distinto (`"Camila"`) y todos los demás datos cambiados. El servidor lo rechazó igualmente, porque la regla `unique` se aplica sobre el documento de identidad, no sobre el nombre.

> **Por qué esto es crítico en un contexto clínico:** si el sistema permitiera dos fichas para la misma persona, un profesional podría abrir la ficha equivocada y no ver alergias o condiciones registradas en la otra. En urgencias, ese error puede tener consecuencias graves.

### 8.3 Consulta de pacientes por el personal autorizado

> **Endpoint:** `GET /api/v1/patients` — 🔒 requiere token
> **Qué demuestra:** que el personal de salud autenticado puede consultar el listado de pacientes.

![Listado de pacientes](capturas/61_pacientes_listar.png)

**Resultado obtenido:** `200 OK` con los pacientes registrados, cada uno mostrando su edad calculada y su preferencia de comunicación. La petición se ejecutó con el token de un usuario de rol `admision`.

### 8.4 Decisión de diseño — por qué no existe `PUT` ni `DELETE` sobre pacientes

> Esta subsección no muestra una captura, sino que **explica una ausencia deliberada** en la documentación de Swagger.

A diferencia de las demás entidades del sistema (`Organization`, `HealthCenter`, `Unit`, `User`), el modelo `Patient` **no expone rutas de edición ni de eliminación**. En la documentación de Swagger, el grupo *Pacientes* contiene únicamente tres operaciones:

| Método | Ruta | Descripción |
|---|---|---|
| `GET` | `/patients` | Listar pacientes |
| `POST` | `/patients` | Autorregistro de paciente |
| `GET` | `/patients/{id}` | Ver un paciente |

Esto **no es una omisión**: es una decisión de diseño definida en `PatientPolicy`, la clase que gobierna quién puede hacer qué sobre esta entidad. Los siete métodos estándar de la política (`viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`) devuelven `false` de forma **absoluta** para todos los roles del personal clínico — `admision`, `categorizacion`, `medico`, e incluso `admin_institucional` y `super_admin`.

Dado que esa regla es incondicional, no se construyeron las rutas correspondientes. Exponer un endpoint `PUT /patients/{id}` que la política va a rechazar **siempre** solo añadiría código muerto y superficie de ataque innecesaria.

> 💡 **El principio aplicado:** *no expongas una puerta que nunca vas a dejar abrir*. Es preferible no construir la ruta que construirla y bloquearla después con una capa de autorización — una puerta que existe puede fallar, una que no existe no.

El contraste con el resto del sistema queda así:

```mermaid
flowchart LR
    subgraph ADMIN["🏛️ Entidades administrativas"]
        direction TB
        A1["Organization<br/>HealthCenter<br/>Unit · User"]
        A2["✅ Crear<br/>✅ Leer<br/>✅ Editar<br/>✅ Desactivar"]
        A1 --- A2
    end

    subgraph PAC["🧏 Paciente"]
        direction TB
        B1["Patient"]
        B2["🔓 Autorregistro público<br/>✅ Leer<br/>❌ Editar<br/>❌ Eliminar"]
        B1 --- B2
    end

    style ADMIN fill:#ecfdf5,stroke:#0f766e,stroke-width:2px
    style PAC fill:#fef2f2,stroke:#f87171,stroke-width:2px
    style A2 fill:#ffffff,stroke:#0f766e
    style B2 fill:#ffffff,stroke:#f87171
```

Lo que sí puede hacer el personal autorizado es **consultar**, como demuestra la sección 8.3. La ficha del paciente es, para el sistema, un dato que se lee pero no se altera desde el panel administrativo.

---

## 9. Hito 2 — Código Temporal de Atención (CTA)

> 💡 **El problema que resuelve el CTA.** Un paciente sordo llega a urgencias y se acerca al mesón de Admisión. El funcionario necesita saber **quién es** para abrir su atención. Pero el paciente no puede decírselo hablando, y escribir su RUT en un papel es lento, propenso a errores y expone datos sensibles a la vista de la sala de espera.
>
> El **CTA** resuelve esto: el paciente genera en su propio teléfono un código corto (`SV-` + 6 dígitos), se lo muestra al funcionario, y ese código **revela la identidad del paciente** al sistema de Admisión.

### 9.1 La decisión de diseño más importante del hito

Antes de escribir una sola línea de código, el diseño original de este hito decía: *"Admisión genera el CTA para el paciente que tiene en pantalla, y luego lo valida"*. Ese planteamiento tenía una **contradicción lógica**:

> Si Admisión necesita tener al paciente ya identificado en pantalla para generar su código, entonces el código **no cumple ninguna función real** — la identificación ya ocurrió antes, por otro medio.

El diseño se corrigió al flujo inverso, que es el implementado:

```mermaid
sequenceDiagram
    autonumber
    actor P as 🧏 Paciente
    participant API as ⚙️ Backend SeñaVida
    actor A as 🏥 Admisión

    Note over P,API: Hito 1 — el paciente ya está registrado

    P->>API: POST /patients/{id}/attention-codes
    Note right of P: 🔓 Sin token — endpoint público
    API-->>P: 201 · código SV-XXXXXX (una sola vez)

    P->>A: Muestra el código en pantalla
    Note over A: Admisión aún NO sabe quién es

    A->>API: POST /attention-codes/validate { code }
    Note left of A: 🔒 Requiere token de admisión
    API-->>A: 200 · nombre + preferencia de comunicación

    Note over A: Ahora sí conoce la identidad<br/>y cómo comunicarse
```

> **La lección de fondo:** el propósito de una credencial de identificación es **revelar** una identidad, no confirmarla. Si el sistema ya conoce la identidad antes de usar la credencial, la credencial está de más.

### 9.2 Parámetros de seguridad implementados

| Parámetro | Valor |
|---|---|
| Formato del código | `SV-` + 6 dígitos, comparación *case-insensitive* |
| Vigencia | **60 minutos** desde su generación |
| Reutilización | **Un solo uso** |
| Códigos activos por paciente | **Máximo 1** — generar uno nuevo invalida el anterior |
| Almacenamiento | **Nunca en claro**: se persiste su hash bcrypt |
| Generación del número | `random_int()` — generador criptográficamente seguro |
| Protección contra fuerza bruta | Límite de frecuencia por IP |

### 9.3 Generación del código (T3)

> **Endpoint:** `POST /api/v1/patients/{id}/attention-codes` — 🔓 **público, sin autenticación**
> **Qué demuestra:** que el paciente genera su propio código sin necesitar credenciales.

![Generación de código CTA](capturas/62_cta_generar.png)

**Resultado obtenido:** `201 Created` con:

```json
{
  "code": "SV-195348",
  "expiresAt": "2026-08-24T20:28:29+00:00"
}
```

La petición se ejecutó a las `19:28:29 GMT` y el código expira a las `20:28:29` — exactamente **60 minutos** después, confirmando la vigencia configurada. Igual que en el autorregistro, el comando `curl` **no incluye header `Authorization`**.

> **El código en claro se devuelve una sola vez.** En la base de datos solo queda su hash bcrypt. Si el paciente pierde el código, no hay forma de recuperarlo — hay que generar uno nuevo. Es el mismo principio con el que se tratan las contraseñas.

### 9.4 Un solo código activo por paciente

> **Qué demuestra:** que generar un código nuevo invalida automáticamente el anterior.

![Segunda generación de CTA](capturas/63_cta_generar_segundo_invalida_anterior.png)

**Resultado obtenido:** `201 Created` con un código **distinto**, `SV-940557`, generado a las `19:29:38` — apenas un minuto después del anterior.

Internamente, antes de insertar el registro nuevo, el sistema ejecuta:

```sql
UPDATE temporary_access_codes
SET status = 'expired'
WHERE patient_id = ? AND status = 'active'
```

El código `SV-195348` de la sección anterior quedó marcado como `expired` en ese instante, aunque su hora de expiración natural aún no había llegado.

**Comprobación en la práctica:**

![Validación de código ya invalidado](capturas/64_cta_validar_parametros.png)

Al intentar validar `SV-195348` después de haber generado `SV-940557`, el sistema responde `422`. El rechazo **no ocurre porque el hash no coincida** — de hecho coincide perfectamente — sino porque el registro ya está en estado `expired`.

> 💡 **Por qué existe esta regla:** si un paciente pudiera tener varios códigos vivos a la vez, un código antiguo olvidado en una captura de pantalla o mostrado a la persona equivocada seguiría siendo utilizable. Con un solo código activo, generar uno nuevo cancela cualquier riesgo anterior.

### 9.5 Rechazo de código inválido

> **Endpoint:** `POST /api/v1/attention-codes/validate` — 🔒 requiere token de `admision`
> **Qué demuestra:** que un código inexistente es rechazado con un mensaje claro y sin filtrar información.

![Validación de código inválido](capturas/65_cta_validar_invalido_422.png)

**Resultado obtenido:** `422 Unprocessable Content` con el mensaje `"El codigo ingresado no es valido."`.

Se envió `SV-000000`, un código que nunca existió. La respuesta es deliberadamente **genérica**: no revela si el código existió alguna vez, si expiró, o si pertenece a otro centro de salud. Cualquier detalle adicional sería información útil para alguien intentando adivinar códigos.

Debajo, Swagger documenta los códigos posibles de este endpoint: `200` (código válido, devuelve datos mínimos del paciente), `401` (no autenticado), `403` (sin permiso o código bloqueado por intentos).

### 9.6 Validación exitosa — el flujo completo ⭐

> **Qué demuestra:** el ciclo completo del CTA funcionando de punta a punta. **Esta es la evidencia central del Hito 2.**

**Paso 1 — el paciente genera su código:**

![Generación del código para validar](capturas/66_cta_generar_para_validacion.png)

`201 Created` con el código `SV-526302`, válido hasta las `21:15:34`. Petición **sin token**.

**Paso 2 — Admisión valida el código:**

![Validación exitosa del CTA](capturas/67_cta_validar_exitoso_200.png)

**Resultado obtenido:** `200 OK` con:

```json
{
  "accessId": "01a0356a-0157-72bf-a2a7-6ffa33a442be",
  "patient": {
    "id": "01a024de-7763-71df-8bb4-42495f3c999c",
    "name": "Juan Soto",
    "communicationPreference": "texto"
  }
}
```

**Aquí está el corazón del sistema.** El funcionario de Admisión escribió únicamente `{"code": "SV-526302"}` — no envió ningún `patient_id`, no seleccionó a nadie de una lista, no sabía de antemano quién era la persona frente a él. El sistema le **reveló** la identidad: se llama Juan Soto, y se comunica **por texto**.

Ese último dato no es un detalle administrativo: le dice al funcionario, antes de intentar nada, **cómo comunicarse con esta persona**. Es exactamente el propósito para el que existe SeñaVida.

> **Nota técnica sobre la validación sin `patient_id`.** Como el código se guarda con hash bcrypt, y bcrypt genera un hash distinto cada vez (por el *salt* aleatorio), **no se puede buscar directamente por hash** en la base de datos. La validación compara el código con `Hash::check()` contra los códigos `active` **del centro de salud del funcionario autenticado**. Ese acotamiento cumple, por construcción, la regla de aislamiento por centro: un funcionario del Hospital A no puede validar el código de un paciente que está en el Hospital B.

### 9.7 Protección contra fuerza bruta

> **Qué demuestra:** que el sistema bloquea intentos repetidos desde una misma IP.

![Rate limiting activado en generación de CTA](capturas/68_cta_rate_limit_429.png)

**Resultado obtenido:** `429 Too Many Requests` con el mensaje `"Demasiados intentos. Intenta de nuevo en 584 segundos."`.

Tras varias generaciones consecutivas desde la misma dirección IP, el sistema cortó el acceso e indicó el tiempo exacto de espera restante.

**Límites configurados:**

| Endpoint | Límite |
|---|---|
| Generación de CTA | 5 intentos / 10 minutos por IP |
| Validación de CTA | 5 intentos / 5 minutos por IP |

> 💡 **Por qué la protección es por IP y no por contador en el registro.** El esquema incluye columnas `failed_attempts` y `max_attempts`, pero **no se incrementan por intento individual**. La razón es lógica: cuando alguien escribe un código que no coincide con ninguno, **no hay un registro específico al cual atribuir el fallo** — el sistema no sabe qué código "pretendía" escribir. Sumar el fallo a un registro arbitrario sería incorrecto.
>
> La protección se resolvió entonces en la capa adecuada: **límite de frecuencia por IP**, que cubre el mismo riesgo (adivinar códigos por fuerza bruta) sin necesitar identificar una víctima concreta. Un código de 6 dígitos tiene un millón de combinaciones; a 5 intentos cada 5 minutos, agotarlas tomaría más de 19 años — muy por encima de los 60 minutos de vigencia.

### 9.8 Lo que queda pendiente para el próximo hito

El contrato del proyecto define **tres** operaciones sobre el CTA. Dos están implementadas y documentadas arriba:

| # | Operación | Estado |
|---|---|---|
| **T3** | Generar el código | ✅ Implementado |
| **T1** | Validar el código | ✅ Implementado |
| **T2** | **Consumir** el código y abrir la atención | ⏭️ Diferido al Hito 3 |

**T2 se difirió deliberadamente**, no por falta de tiempo: la respuesta de ese endpoint *es* una **sesión médica** (`MedicalSession`), y ese modelo aún no existe. Construir el endpoint antes que el modelo que devuelve habría sido imposible. Se implementará junto con la entidad central del sistema, en el hito siguiente.

> **Validar y consumir son operaciones distintas a propósito.** Validar responde *"¿quién es esta persona?"* y puede repetirse. Consumir responde *"abramos su atención"* y ocurre **una sola vez**, marcando el código como usado. Separarlas permite que Admisión confirme la identidad antes de comprometerse a abrir una atención.

---

## 10. Hito 3 — Sesión Médica

> 💡 **La pieza que sostiene todo lo demás.** Hasta aquí, el sistema podía identificar a un paciente (Hito 1) y verificar su identidad ante Admisión mediante un código (Hito 2). Pero seguía faltando lo esencial: **la atención misma**. `MedicalSession` es la entidad de la que cuelga todo lo clínico — el chat, los signos vitales, la categorización, las notas médicas, los consentimientos. Sin ella, nada de eso tiene dónde existir.
>
> Este hito también cierra el pendiente que quedó explícito en la sección 9.8: el endpoint que **consume** el código de atención, imposible de construir antes porque su respuesta *era* una sesión médica.

### 10.1 Dos decisiones de diseño resueltas antes de escribir código

Igual que en el Hito 2 —donde una pregunta a tiempo evitó construir un CTA inútil—, este hito comenzó resolviendo dos ambigüedades del contrato.

**D-07 · El estado duplicado.** El prototipo del frontend guardaba el estado de la atención en **dos campos a la vez**: un `status` enumerado (`in_triage`) y un `currentStage` de texto libre (`"Categorización"`). Ambos decían lo mismo.

> **El problema de duplicar la fuente de verdad.** Si un día alguien actualiza uno y olvida el otro, la sesión queda contradictoria: `status` dice que está en Categorización, `currentStage` dice que sigue en Admisión. ¿Cuál cree la interfaz? El error no es *si* ocurre, sino *cuándo*.

**Resolución adoptada:** un único campo `status` con 6 valores canónicos. El texto en español **no se guarda: se calcula** al momento de servir la respuesta.

```php
// app/Enums/MedicalSessionStatus.php
public function label(): string
{
    return match ($this) {
        self::InAdmission   => 'Admisión',
        self::InTriage      => 'Categorización',
        self::InMedicalCare => 'Consulta Médica',
        self::Closed        => 'Cerrado',
        self::Cancelled     => 'Cancelada',
        self::Expired       => 'Expirada',
    };
}
```

Verificado en Tinker antes de construir nada más:

```php
> \App\Enums\MedicalSessionStatus::InTriage->label();
= "Categorización"
```

**T2 y S1 · Una duplicación en el propio contrato.** Revisando los endpoints definidos, se detectó que **dos rutas distintas hacían lo mismo**: `POST /attention-codes/{id}/consume` (T2) y `POST /medical-sessions` (S1), ambas creaban una sesión a partir de un código validado.

**Resolución adoptada:** conservar solo S1. Consumir el código pasó a ser un **efecto interno** de abrir la atención, no un endpoint aparte. Fundamento: S1 acepta datos que T2 no contemplaba (motivo de consulta, alergias), y en REST, crear un recurso es `POST` sobre la colección de ese recurso — `POST /medical-sessions` *dice* lo que hace.

### 10.2 El recorrido de una atención, por rol

```
in_admission ──(admisión)──> in_triage ──(categorización)──> in_medical_care ──(médico)──> closed
```

Cada flecha tiene **un solo dueño**. No basta con tener el rol correcto: hay que ser el dueño de *ese tramo específico*, y estar en la misma unidad.

| Acción | Quién | Condición adicional |
|---|---|---|
| Abrir | `admision` | — |
| Ver | `admision`, `categorizacion`, `medico` | Misma unidad |
| Avanzar `in_admission → in_triage` | `admision` | Misma unidad |
| Avanzar `in_triage → in_medical_care` | `categorizacion` | Misma unidad |
| Cerrar | `medico` | Misma unidad **y** sesión en `in_medical_care` |

> **El aislamiento es por unidad, no solo por hospital.** Un funcionario de Urgencia Infantil no puede operar sobre una atención de Urgencia Adulto, aunque ambas pertenezcan al mismo centro de salud.
>
> **`super_admin` y `admin_institucional` quedan excluidos por completo.** El contrato es explícito: *"El administrador gestiona la plataforma; no accede a información clínica de pacientes."*

### 10.3 S1 · Abrir la atención

> **Endpoint:** `POST /api/v1/medical-sessions` — 🔒 rol `admision`
> **Qué demuestra:** que el CTA validado se consume y nace la atención.

**Petición enviada:**

```json
{
  "access_code_id": "01a0372d-cf65-7320-a1dd-bd92c68c3761",
  "code": "SV-340860",
  "reason_of_visit": "Control post-operatorio, dolor leve",
  "allergies": []
}
```

**Resultado obtenido:** `201 Created`.

```json
{
  "id": "01a0372e-b3af-72ca-b30a-d4f8cbc35440",
  "ctaCode": "SV-340860",
  "status": "in_admission",
  "statusLabel": "Admisión",
  "isWritable": true,
  "reasonOfVisit": "Control post-operatorio, dolor leve",
  "patient": {
    "name": "María Fernández",
    "age": 41,
    "communicationPreference": "senas",
    "allergies": []
  },
  "healthCenterName": "Hospital San Rafael",
  "unitName": "Urgencia Adulto",
  "createdBy": { "name": "Enfermero Uno SR" }
}
```

**Tres detalles que vale la pena señalar:**

| Campo | Por qué importa |
|---|---|
| `ctaCode` | Cierra el `TODO` que el frontend arrastraba hardcodeado en `DashboardContainer.tsx:262`. El código se copia al abrir la atención — cuando **ya está consumido** y por tanto gastado, lo que hace aceptable persistirlo |
| `statusLabel` | La etiqueta derivada del enum, nunca almacenada. D-07 resuelto en la práctica |
| `isWritable` | Le dice al frontend si debe habilitar o deshabilitar los formularios, sin que tenga que repetir la lógica de estados |

> **Todo dentro de una transacción.** Crear la sesión y marcar el código como `consumed` ocurren **juntos o ninguno**. Si algo falla a mitad de camino, PostgreSQL deshace ambos — el mismo mecanismo que se observó cuando una migración falló por incompatibilidad de tipos y no dejó tablas a medio construir.

### 10.4 S3 · Listar las atenciones activas de la unidad

> **Endpoint:** `GET /api/v1/medical-sessions/active` — 🔒 rol clínico
> **Qué demuestra:** que el panel puede consultar todas las atenciones en curso de su unidad.

**Resultado obtenido:** `200 OK` con un **array de tres sesiones**, todas en `Urgencia Adulto` del Hospital San Rafael, ordenadas por hora de inicio:

```json
{
  "success": true,
  "data": [
    { "id": "01a039b7-...", "status": "in_admission", "patient": { "name": "Prueba Errores" } },
    { "id": "01a03c13-...", "status": "in_admission", "patient": { "name": "Paciente Prueba Uno" } },
    { "id": "01a03c15-...", "status": "in_admission", "patient": { "name": "Paciente Prueba Dos" } }
  ]
}
```

> **Una corrección al contrato original.** El contrato describía *"la sesión activa"* en singular. Se implementó como **lista** porque una unidad de urgencias puede tener varios pacientes en curso simultáneamente. Devolver solo la más reciente habría **ocultado pacientes** del panel — un error silencioso y potencialmente grave. La captura confirma el caso real con dos atenciones abiertas a la vez.

### 10.5 S4 · Avanzar de etapa

**Resultado obtenido:** `200 OK`. Con el token de `admision`, la sesión del "Paciente Prueba Uno" pasó de `in_admission` a:

```json
{ "status": "in_triage", "statusLabel": "Categorización" }
```

**La prueba inversa — el mismo usuario, la etapa siguiente:**

**Resultado obtenido:** `403 Forbidden`. Con el **mismo token de `admision`**, repetir la petición sobre la misma sesión —ya en `in_triage`— es rechazado: ese tramo le pertenece a `categorizacion`.

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN_ROLE",
    "message": "Esta etapa corresponde avanzarla a categorizacion, no a tu rol."
  }
}
```

**Y el bloqueo por unidad:**

**Resultado obtenido:** `403 Forbidden`. Con un token de `categorizacion` —rol correcto para esta etapa— pero de **Urgencia Infantil**, sobre una sesión de **Urgencia Adulto**:

```json
{
  "success": false,
  "error": {
    "code": "FORBIDDEN_CENTER",
    "message": "Esta atencion pertenece a otra unidad."
  }
}
```

Un usuario de `categorizacion` de **Urgencia Infantil** no puede avanzar una atención de **Urgencia Adulto**, aunque ambos trabajen en el mismo hospital.

### 10.6 D-23 · El salto de emergencia

> **Qué problema resuelve.** El flujo normal obliga a pasar por las tres etapas en orden. Pero llega un paciente con un paro cardiorrespiratorio: no hay tiempo de esperar a Categorización. El contrato es explícito al respecto:
>
> *"El backend **DEBE** permitir el avance sin categorización previa... Existen situaciones de riesgo vital donde omitir la categorización formal es lo clínicamente correcto. Bloquearlo sería peligroso."*

**Endpoint:** el mismo `PATCH /medical-sessions/{id}/stage`, con un modo especial en el body.

**Preparación del caso de prueba — un paciente y su atención, de cero:**

Con la atención abierta y todavía en `in_admission`, se activó el salto:

**Petición enviada:**

```json
{
  "emergency": true,
  "reason": "Paciente con dolor toracico intenso y disnea, sospecha de infarto agudo al miocardio."
}
```

**Resultado obtenido:** `200 OK`, saltando directamente de `in_admission` a Consulta Médica:

```json
{
  "status": "in_medical_care",
  "statusLabel": "Consulta Médica",
  "triageSkipped": true,
  "triageSkipReason": "Paciente con dolor toracico intenso y disnea, sospecha de infarto agudo al miocardio.",
  "triageSkippedBy": { "name": "Dr. Uno SR" }
}
```

**Los controles aplicados — y los deliberadamente omitidos:**

| Control | Decisión |
|---|---|
| Solo `medico` puede activarlo | ✅ Aplicado |
| Motivo obligatorio, mínimo 30 caracteres | ✅ Aplicado |
| Registro permanente de qué, por qué y quién | ✅ Aplicado (`triageSkipped`, `triageSkipReason`, `triageSkippedBy`) |
| Validar clínicamente que sea "suficientemente grave" | ❌ **Descartado a propósito** |
| Requerir aprobación de un segundo profesional | ❌ **Descartado a propósito** |

> **Por qué se descartaron los dos últimos.** Un sistema de software **no puede verificar médicamente** si algo es una emergencia real — eso lo decide el criterio clínico del profesional en el momento. Una lista cerrada de diagnósticos permitidos sería imposible de mantener y daría falsa seguridad. Y cualquier paso adicional de aprobación introduce fricción **en el momento exacto donde la fricción mata**.
>
> **El principio aplicado:** el control no está en impedir el salto, sino en hacerlo **imposible de ocultar**. El mínimo de 30 caracteres existe justamente para eso: *"emergencia."* (11 caracteres) no sirve para auditar; el motivo del ejemplo sí.

### 10.7 S5 · Cerrar la atención

**Resultado obtenido:** `200 OK`.

```json
{
  "status": "closed",
  "statusLabel": "Cerrado",
  "isWritable": false,
  "endedAt": "2026-08-25T20:44:23.000000Z",
  "closureReason": "completed",
  "summary": "Paciente estabilizado tras sospecha de IAM. Se deriva a unidad coronaria.",
  "triageSkipped": true,
  "triageSkipReason": "Paciente con dolor toracico intenso y disnea, sospecha de infarto agudo al miocardio.",
  "closedBy": { "name": "Dr. Uno SR" }
}
```

Obsérvese que **el registro de la emergencia sobrevive al cierre**: `triageSkipped` sigue en `true` con su motivo original, junto al resumen de egreso. Toda la historia de la atención queda en un solo lugar, auditable.

### 10.8 Un hueco de seguridad encontrado durante las pruebas

> **Cómo se descubrió.** Al probar S5 por primera vez, se intentó cerrar una atención que todavía estaba en `in_triage` — sin haber pasado nunca por Consulta Médica. El sistema **lo permitió**, devolviendo `200`.

**Por qué era grave:** significaba que un médico podía cerrar la atención de un paciente **que nunca llegó a verse con él** — todavía en la sala de espera de Categorización. El resumen de egreso podría estar describiendo una consulta que jamás ocurrió.

**La causa:** `close()` en la Policy validaba **rol** y **unidad**, pero no la **etapa**.

**La corrección aplicada:**

```php
public function close(User $user, MedicalSession $session): Response
{
    if ($user->role !== 'medico') { ... }
    if (! $this->mismaUnidad($user, $session)) { ... }

    // La condición que faltaba:
    if ($session->status !== MedicalSessionStatus::InMedicalCare) {
        return Response::deny('INVALID_STAGE_TRANSITION|Esta atencion aun no ha llegado a Consulta Medica.');
    }

    return Response::allow();
}
```

**Verificado tras la corrección**, sobre una sesión en `in_admission`:

```php
> $medico->can('close', $maria);
= false
```

**El hallazgo del bug, documentado con su propia evidencia — antes y después.** Al agregar el campo `code`, apareció un segundo problema: Laravel convierte `AuthorizationException` en `AccessDeniedHttpException` antes de que el handler la vea, así que la regla que separaba `code` de `message` nunca se ejecutaba.

*Antes de la corrección* — el código quedó pegado dentro del mismo texto:

```json
{ "error": { "message": "INVALID_STAGE_TRANSITION|Esta atencion aun no ha llegado a Consulta Medica." } }
```

*Después* de capturar `AccessDeniedHttpException` en vez de `AuthorizationException` — la misma prueba, exacta:

```json
{ "error": { "code": "INVALID_STAGE_TRANSITION", "message": "Esta atencion aun no ha llegado a Consulta Medica." } }
```

> 💡 **La lección.** El hueco no apareció leyendo el código, sino **probando un caso que no era el camino feliz**. Vale la pena diseñar las pruebas preguntando *"¿qué pasa si alguien hace esto fuera de orden?"*, no solo *"¿funciona cuando todo va bien?"*.

### 10.9 Middleware `EnsureMedicalSessionIsActive` — Hito 4 de Fase 4

> **Nota de alcance.** Aunque se construyó en la misma sesión de trabajo que la Sesión Médica, este middleware corresponde formalmente al **Hito 4** de Fase 4 según el mapa del proyecto — un hito propio, no un detalle del Hito 3. Con esta pieza, **Fase 4 (Paciente, CTA y Sesión Médica) queda completa: los cuatro hitos definidos están construidos y verificados**.

> **Qué problema resuelve.** El propio contrato marca como riesgo crítico: *"R4 — El cierre de sesión no bloquea escrituras"*. Sin esta pieza, nada impediría escribir signos vitales o notas clínicas sobre una atención ya cerrada.

Un **middleware** es una capa que intercepta la petición **antes** de que llegue al controlador — el mismo mecanismo de `auth:sanctum`, pero para una regla de negocio propia.

```
Petición → auth:sanctum → session.active (¿está cerrada? corta aquí) → Controlador
```

**Resultado obtenido:** `409 Conflict` al intentar avanzar la sesión de María, ya cerrada previamente (sección 10.7).

```json
{
  "success": false,
  "error": {
    "code": "SESSION_ALREADY_CLOSED",
    "message": "Esta atencion ya esta cerrada. No se permiten mas cambios."
  }
}
```

> **Por qué se construyó ahora, si ningún módulo clínico existe todavía.** Precisamente para que cuando se construyan (signos vitales, notas, chat), **no haya que copiar la validación en cada controlador**. Basta agregar `->middleware('session.active')` a la ruta. Un día alguien olvidaría copiar ese `if`, y ahí aparecería el hueco real.

---

### 10.10 Investigación de un gap que resultó no existir

> **Cómo surgió.** Tras corregir el hueco de `close()`, se planteó una pregunta más amplia: *¿qué pasa si a cualquier endpoint de `medical-sessions` le llega un identificador que ni siquiera tiene forma de UUID?* Se decidió comprobarlo en vez de asumir.

**Primer intento — un UUID válido pero inexistente:**

**Resultado obtenido:** `404 Not Found`, manejado correctamente por Laravel — aunque en ese momento el mensaje todavía exponía el nombre interno de la clase (`App\Models\MedicalSession`), el gap descrito en la sección 11.2.

**Segundo intento — provocar el mismo error desde Swagger con un texto que ni siquiera parece un UUID:**

**Resultado obtenido:** la propia documentación interactiva lo rechazó *antes* de que la petición saliera del navegador — `"Value must be a Guid"` —, porque el atributo `#[OA\Parameter(...)]` del endpoint ya declara `format: 'uuid'`.

**Conclusión:** para probar el caso real hubo que saltarse Swagger y usar `curl` directo. El resultado (documentado en la sección 11.2) fue que Laravel maneja ambos casos —UUID inexistente y texto inválido— de la misma forma segura, sin el `QueryException` sin capturar que se sospechaba. La investigación no confirmó el bug temido, pero sí confirmó que el sistema es más robusto de lo esperado — un resultado tan válido como encontrar un error.

### 10.11 Verificación cruzada: la sesión cerrada de María sigue siendo consultable

Como parte de confirmar que el arreglo del `404` no había roto el camino feliz, se repitió `GET /medical-sessions/{id}` sobre la sesión de María —ya cerrada— con su historial completo:

**Resultado obtenido:** `200 OK`, con `status: "closed"` y todo el detalle intacto: `ctaCode`, `closureReason`, `summary`, unidad y centro. Confirma que cerrar una atención no le quita capacidad de **lectura** — solo de escritura, que es exactamente lo que exige el middleware `session.active`.

---

## 11. Manejo unificado de errores

> 💡 **Cómo surgió esta sección.** No estaba en el plan del hito. Al revisar una captura de error, se notó que la respuesta traía una traza de 40 líneas con rutas absolutas del servidor (`C:\laragon\www\...`), mientras que otros endpoints devolvían un JSON limpio. Dos formatos distintos para el mismo tipo de error, en la misma API.

### 11.1 El problema

**Formato limpio (endpoints del Hito 2):**

```json
{ "success": false, "error": { "message": "El codigo ingresado no es valido." } }
```

**Formato crudo (los `abort()` nuevos):**

```json
{
  "message": "Esta atencion ya esta cerrada.",
  "exception": "Symfony\\Component\\HttpKernel\\Exception\\HttpException",
  "file": "C:\\laragon\\www\\senavida-backend-eva2\\vendor\\laravel\\...",
  "line": 1447,
  "trace": [ ... 40 líneas ... ]
}
```

Dos problemas a la vez: **inconsistencia** (el frontend tendría que manejar dos formas de leer un error) y **exposición de estructura interna** del servidor.

### 11.2 Los cuatro tipos de excepción que hubo que capturar

La solución fue centralizar el manejo en `bootstrap/app.php`, sin tocar ningún controlador. Pero requirió entender que **cada tipo de problema lanza una clase distinta**:

| Pregunta que responde | Excepción | HTTP |
|---|---|:---:|
| *¿Quién eres?* | `AuthenticationException` | 401 |
| *¿Puedes hacer esto?* | `AccessDeniedHttpException` | 403 |
| *¿Existe lo que pides?* | `NotFoundHttpException` | 404 |
| *¿Tiene sentido ahora?* | `ApiException` (propia) | 403/409/410/422 |

> ⚠️ **Un hallazgo que costó dos intentos: Laravel convierte excepciones en el camino.**
>
> El primer intento capturó `AuthorizationException` (la que lanza una Policy al rechazar) y `ModelNotFoundException` (la que lanza `findOrFail`). **Ninguna de las dos se ejecutó nunca.** El motivo: Laravel las transforma antes de que lleguen al handler.
>
> | Se lanza | Llega al handler como |
> |---|---|
> | `AuthorizationException` | `AccessDeniedHttpException` |
> | `ModelNotFoundException` | `NotFoundHttpException` |
>
> Hay que capturar la **clase convertida**, no la original. Además, el orden importa: ambas convertidas **extienden de `HttpException`**, así que una regla genérica sobre `HttpException` colocada antes las absorbería a las dos. **Lo específico va antes que lo general.**

### 11.3 El código de error legible por máquina

El contrato es explícito en §18.2:

> *"El frontend **DEBE** ramificar por [el `code`] y **NUNCA** por el texto de `message`."*

La razón es práctica: el texto de un mensaje puede cambiar de redacción en cualquier momento; el identificador `SESSION_ALREADY_CLOSED` es un contrato estable.

> **Nota:** el descubrimiento de esta corrección, con su evidencia antes/después, está documentado en la sección 10.8.

**Resultado obtenido:**

```json
{
  "success": false,
  "error": {
    "code": "INVALID_STAGE_TRANSITION",
    "message": "Esta atencion aun no ha llegado a Consulta Medica."
  }
}
```

**Cómo se implementó:**

| Pieza | Rol |
|---|---|
| `App\Exceptions\ApiException` | Errores de negocio con código propio. Reemplaza a `abort()` |
| `Response::deny('CODIGO\|mensaje')` en las Policies | Cada rechazo explica su motivo **y** su código |
| Handler en `bootstrap/app.php` | Normaliza el formato de toda la API en un solo lugar |

> **Efecto retroactivo.** Al centralizar el manejo, los `abort()` del Hito 2 (CTA) también quedaron con el formato limpio, **sin haber tocado ese controlador**. Un solo cambio arregló código escrito días antes.
>
> **Pendiente:** esos mismos endpoints del CTA todavía devuelven `message` sin `code`. El envoltorio ya es correcto; falta migrarlos de `abort()` a `ApiException` en una segunda pasada.

### 11.4 Corrección de seguridad detectada durante el hito

Al construir `MedicalSessionPolicy` se revisó `PatientPolicy` como referencia, y se encontró que **`super_admin` tenía acceso de lectura a las fichas de pacientes** — contradiciendo directamente la tabla de segregación de datos por rol del contrato, que le asigna `❌` en esa fila.

```php
// Antes — super_admin incluido:
return in_array($user->role, ['admision', 'categorizacion', 'medico', 'super_admin']);

// Después:
return in_array($user->role, ['admision', 'categorizacion', 'medico']);
```

**Verificado en ejecución:**

```php
> $admin = \App\Models\User::where('role', 'super_admin')->first();
> $patient = \App\Models\Patient::first();
> $admin->can('view', $patient);
= false
```

> **Cómo se coló originalmente.** Es un patrón común: durante el desarrollo se agrega `super_admin` a los permisos *"por si acaso, para poder probar todo sin trabas"*. Tiene sentido mientras se construye — el problema es que esa comodidad **queda fija en el código de producción**.

---

## 12. Estado actual de la base de datos

> **Qué demuestra:** que todas las tablas del sistema, incluidas las de los tres hitos, están efectivamente creadas en PostgreSQL.

**Resultado obtenido:** las **dieciséis** migraciones del proyecto figuran en estado **`Ran`**, sin ninguna pendiente ni fallida.

```
  Migration name ..................................................... Batch / Status
  0001_01_01_000000_create_users_table ...................................... [1] Ran
  0001_01_01_000001_create_cache_table ...................................... [1] Ran
  0001_01_01_000002_create_jobs_table ....................................... [1] Ran
  2026_08_07_033306_create_personal_access_tokens_table ..................... [1] Ran
  2026_08_07_044802_create_audit_logs_table .................................. [1] Ran
  2026_08_08_040215_create_organizations_table ............................... [1] Ran
  2026_08_08_041854_create_health_centers_table ............................... [1] Ran
  2026_08_08_044552_create_units_table ....................................... [1] Ran
  2026_08_08_052132_add_foreign_keys_to_users_table ........................... [1] Ran
  2026_08_21_045743_create_patients_table ..................................... [2] Ran
  2026_08_21_045744_create_patient_contacts_table ............................. [2] Ran
  2026_08_24_190328_create_temporary_access_codes_table ........................ [3] Ran
  2026_08_25_000001_create_medical_sessions_table .............................. [4] Ran
  2026_08_25_000002_add_consumed_status_to_temporary_access_codes_table ........ [4] Ran
  2026_08_25_000003_add_cta_code_to_medical_sessions_table ...................... [5] Ran
  2026_08_25_000004_add_triage_skip_to_medical_sessions_table .................. [6] Ran
```

```
  2026_08_21_045743_create_patients_table ..................................... [2] Ran
  2026_08_21_045744_create_patient_contacts_table ............................. [2] Ran
  2026_08_24_190328_create_temporary_access_codes_table ....................... [3] Ran
  2026_08_25_000001_create_medical_sessions_table ............................. [4] Ran
  2026_08_25_000002_add_consumed_status_to_temporary_access_codes_table ....... [4] Ran
  2026_08_25_000003_add_cta_code_to_medical_sessions_table .................... [5] Ran
  2026_08_25_000004_add_triage_skip_to_medical_sessions_table ................. [6] Ran
```

La columna **Batch** cuenta por sí sola la historia del proyecto:

| Batch | Migraciones | Corresponde a |
|:---:|---|---|
| **1** | `users`, `cache`, `jobs`, `personal_access_tokens`, `audit_logs`, `organizations`, `health_centers`, `units`, `add_foreign_keys_to_users_table` | Base de Laravel, Sanctum y módulo administrativo |
| **2** | `patients`, `patient_contacts` | **Hito 1** — Modelo de Paciente |
| **3** | `temporary_access_codes` | **Hito 2** — Código Temporal de Atención |
| **4** | `medical_sessions`, `add_consumed_status_to_temporary_access_codes` | **Hito 3** — Sesión Médica |
| **5** | `add_cta_code_to_medical_sessions` | Hito 3 — exposición del `ctaCode` |
| **6** | `add_triage_skip_to_medical_sessions` | Hito 3 — salto de emergencia (D-23) |

> **Qué es un *batch*:** cada vez que se ejecuta `php artisan migrate`, Laravel agrupa bajo un mismo número todas las migraciones aplicadas en esa corrida. Esto permite deshacer un grupo completo con `migrate:rollback` sin afectar los anteriores — y, como efecto secundario útil, deja registrado el orden cronológico real en que se construyó el sistema.

> **Los batches 5 y 6 cuentan una historia honesta.** No estaban planificados: surgieron de dos problemas encontrados **después** de dar por terminada la tabla principal. El batch 5 nació al notar que `ctaCode` —requisito explícito del contrato— no se estaba exponiendo. El batch 6, al detectar que el sistema no permitía el salto de emergencia que el propio contrato exige. Construir por partes y volver a revisar es normal; ocultarlo en una sola migración "limpia" habría sido menos fiel a lo que realmente pasó.

---

## 13. Detalle técnico destacado del Hito 3

### 13.1 Índice único parcial — «un paciente, una atención abierta»

El contrato exige (RF-027) que un paciente no pueda tener dos atenciones abiertas simultáneamente. Un `unique()` normal de Laravel **no sirve**: diría *"este paciente aparece una sola vez en toda la tabla, para siempre"*, rompiendo el sistema en su segunda visita.

La solución es una característica nativa de PostgreSQL:

```sql
CREATE UNIQUE INDEX medical_sessions_one_open_per_patient
ON medical_sessions (patient_id)
WHERE status NOT IN ('closed', 'cancelled', 'expired')
```

**Único, pero solo entre las filas que cumplen esa condición.** El mismo paciente puede tener muchas atenciones a lo largo del tiempo, pero nunca dos **abiertas** a la vez.

> **Doble capa, a propósito.** El índice garantiza la integridad a nivel de base de datos; una verificación previa en el controlador devuelve un `409` con mensaje claro. Sin la segunda, el usuario vería un error `500` crudo de Postgres en vez de una explicación.

### 13.2 Dos errores de implementación y qué enseñaron

**UUID no es lo mismo que ULID.** La primera migración falló:

```
SQLSTATE[42804]: Datatype mismatch
Key columns "patient_id" ... and "id" ... are of incompatible types: character and uuid.
```

Se había usado `$table->ulid()` donde el proyecto usa `uuid`. Se parecen a simple vista (ambos empiezan con `01a...`) pero PostgreSQL los tipa distinto.

> **Un detalle tranquilizador:** las migraciones corren dentro de una **transacción**. Como falló a mitad de camino, PostgreSQL deshizo todo automáticamente — no quedó ninguna tabla a medias.

**`$fillable` descarta campos en silencio.** Tras agregar la columna `cta_code`, la respuesta seguía devolviendo `"ctaCode": null` — sin ningún error.

La causa: `$fillable` es una **lista blanca**. Eloquent solo acepta de un `create()` los campos declarados ahí; cualquier otro lo **descarta sin avisar**. Es una protección contra *mass assignment* (evitar que alguien cuele un campo inesperado desde un formulario), pero el costo es que un campo legítimo olvidado se pierde en silencio.

---

### 13.3 Fase 4 — Cierre

| Hito | Qué construyó | Estado |
|:---:|---|:---:|
| 0 | Saneamiento técnico + Swagger | ✅ |
| A–E | CRUD administrativo completo (4 entidades, Policy, multitenancy) | ✅ |
| 1 | Modelo Paciente (autorregistro público) | ✅ |
| 2 | CTA — generar y validar | ✅ |
| 3 | Sesión Médica — 5 endpoints, Policy por rol/unidad/etapa, salto de emergencia | ✅ |
| 4 | Middleware de sesión activa | ✅ |

**Fase 4 — Paciente, CTA y Sesión Médica — completa.** Los cuatro hitos definidos en el mapa del proyecto están construidos, probados en ejecución y documentados. La siguiente etapa, **Fase 5 — Chat y Consentimientos** —que el propio contrato describe como *"el núcleo del producto"*— ya está en curso; ver sección 14.

---

## 14. Fase 5 — Chat y Consentimientos (completa)

> 💡 **El núcleo comunicacional del producto.** Esta fase construye el canal de mensajes de la atención (chat) y el sistema de permisos del paciente (consentimientos). Antes de tocar esas dos entidades, fue necesario resolver dos prerrequisitos que el propio contrato dejaba pendientes: **cómo se autentica un paciente** (A-03, decidido pero no implementado) y **de dónde salen los pictogramas** que el chat va a usar.

### 14.1 Hito 5.0 — Acceso del paciente (token derivado del CTA)

**El problema.** Hasta este hito, el paciente no tenía ninguna forma de probar su identidad ante la API. Cuatro endpoints de esta fase son exclusivos del paciente (aprobar, rechazar y revocar un consentimiento, y confirmar un mensaje) — sin autenticación, ninguno se podía siquiera construir con seguridad.

**Decisión de diseño resuelta antes de escribir código.** El contrato original decía que el canje del código (CTA) debía validar que el código "no estuviera usado". Pero en el Hito 3 (Fase 4) se decidió que el endpoint de consumo del código (T2) se disolvió dentro de la apertura de la atención (S1): al abrir la atención, Admisión **ya consume** el código.

> **El problema de validar "no usado".** Si el redeem del paciente buscara un código sin usar, nunca lo encontraría — el código ya quedó marcado `consumed` en el paso anterior. El paciente jamás podría entrar a su propia conversación.

**Resolución adoptada:** el redeem no valida contra la tabla del código original, sino contra `medical_sessions.cta_code`, exigiendo que la atención siga abierta. El código, que ya cumplió su función de "abrir la puerta", pasa a significar "esta es mi conversación actual" — y la vigencia queda resuelta gratis: si la atención se cierra, el acceso del paciente muere con ella.

**La solución técnica: token acotado a una sola atención.**

```php
$patient->createToken('patient-portal', ["session:{$session->id}"]);
```

Un token de Sanctum grabado con una *ability* específica — no abre todo el sistema, solo la atención a la que pertenece. Se verificó en Tinker el ciclo completo:

```
> $patient->createToken('patient-portal', [...]);
= Laravel\Sanctum\NewAccessToken { tokenable_type: "App\Models\Patient", ... }

> $patient->tokens()->count();
= 1

> $session->closeSession('completed', 'Resumen de prueba...', $user);
> $patient->tokens()->count();
= 0
```

El token murió automáticamente al cerrar la atención, confirmando la regla del contrato de que el acceso del paciente termina con la atención misma.

**Segregación de identidad.** Con Sanctum, `$request->user()` ahora puede devolver un `Patient` o un `User`, dependiendo del token. Se construyeron dos middlewares — `EnsurePatientToken` y `EnsureStaffToken` — que actúan **antes** de cualquier Policy, evitando que un `Patient` reviente una Policy escrita pensando en `$user->role`.

### 14.2 Hito 5.1 — Catálogo de Pictogramas

**El problema.** El chat necesita referenciar pictogramas mediante una clave foránea real, no una columna huérfana. Además, el prototipo original tenía dos defectos que el contrato señala explícitamente: el símbolo de cada pictograma vivía en un `switch` de código (cualquier pictograma nuevo aparecía con un ícono genérico), y el color se guardaba como clases de Tailwind crudas en la base de datos — acoplamiento fuerte entre datos y presentación.

**Resolución adoptada:** se creó el catálogo (`PictogramCategory` + `Pictogram`) desde cero, corrigiendo ambos defectos. El símbolo (`emoji`) es ahora una columna obligatoria — un dato, no código. El color se reemplazó por un token semántico (`severity`: `critical`, `warning`, `info`, `neutral`); el frontend decide cómo pintarlo, el backend solo comunica el significado clínico.

**Separación de responsabilidades entre roles de administración.** La matriz de capacidades del contrato marca "Gestionar pictogramas" como exclusivo de `admin_institucional` — **sin incluir a `super_admin`**, a pesar de ser el rol de mayor alcance del sistema. No es un descuido: `super_admin` gestiona la *estructura* del sistema (organizaciones, centros, unidades) y es un rol "libre", sin centro asociado; `admin_institucional` gestiona la *operación clínica* de un centro específico, y los pictogramas son contenido operativo de esa atención diaria. Esta distinción ya se había confirmado en el Hito 1, donde incluso `super_admin` tiene `return false` explícito para editar la ficha de un paciente.

Esta separación se verificó con evidencia HTTP real, ejecutada desde Swagger con dos tokens de usuarios reales:

![Listado de categorías de pictogramas](capturas/70_pictograms_categorias_200.png)

*`GET /pictogram-categories` — respuesta 200 con las 4 categorías del catálogo, en el orden definido por `sort_order`. Endpoint accesible tanto a personal de salud como al paciente, según exige el contrato: ambos necesitan este catálogo para construir mensajes.*

![Listado de pictogramas activos](capturas/71_pictograms_listar_200.png)

*`GET /pictograms` — respuesta 200 con los 9 pictogramas sembrados, ordenados por categoría y luego por posición. Cada uno incluye su `emoji` real y su `severity` como token semántico, nunca una clase de estilo.*

![Intento de creación rechazado para Super Admin](capturas/72_pictograms_crear_forbidden_403.png)

*`POST /pictograms` con el token de **Super Admin** — rechazado con `403 FORBIDDEN_ROLE`. Esta es la evidencia central del hito: confirma en ejecución, no solo en el diseño, que ni siquiera el rol de mayor jerarquía puede gestionar el catálogo de pictogramas — es responsabilidad exclusiva de `admin_institucional`.*

![Creación exitosa con el rol correcto](capturas/73_pictograms_crear_exitoso_201.png)

*`POST /pictograms` con el token de **Admin Institucional** — mismo body, mismo endpoint, `201 Created`. El pictograma de prueba fue eliminado y los tokens revocados tras la verificación, dejando el catálogo en su estado real de 9 pictogramas.*

### 14.3 Hito 5.2 — `ChatMessage`: el chat de la atención

**El problema.** Es la funcionalidad central del producto — el canal de mensajes entre paciente y personal de salud. El análisis del prototipo detectó una vulnerabilidad real: el frontend armaba el mensaje completo, **incluyendo quién lo enviaba**, directamente en el navegador. Cualquiera con acceso a las herramientas de desarrollo podía modificar ese código y hacerse pasar por otra persona — por ejemplo, un paciente enviando un mensaje que aparentara venir del médico.

**Resolución adoptada: el backend deriva la identidad, nunca confía en el cliente.**

```php
if ($user instanceof Patient) {
    $senderType = 'patient';
    $senderId   = null; // el contrato exige NULL para el paciente
    $senderName = $user->name;
    $origin     = MessageOrigin::Patient;
} else {
    $senderType = 'staff';
    $senderId   = $user->id;
    $origin     = match ($user->role) {
        'admision'       => MessageOrigin::Admission,
        'categorizacion' => MessageOrigin::Triage,
        'medico'         => MessageOrigin::Doctor,
    };
}
```

El cliente nunca envía `senderType`, `senderId`, `senderName` ni `origin` — el `FormRequest` de este endpoint ni siquiera los valida, por lo que cualquier valor que el cliente intentara mandar en esos campos se ignora por completo.

**`senderName` como copia, no como relación en vivo.** Se guarda el nombre del emisor directamente en la fila del mensaje. Si el médico cambia de nombre o se desactiva su cuenta meses después, el mensaje histórico debe seguir mostrando quién lo escribió *en ese momento* — es un registro clínico-legal, no un dato que deba actualizarse retroactivamente.

**Verificación con tres actores reales de la misma atención.** Se probó el ciclo completo en Swagger: un médico enviando un mensaje, la paciente dueña de esa atención canjeando su código y enviando otro, y el médico marcando el mensaje de la paciente como leído.

![Mensaje enviado por el médico](capturas/74_chat_medico_crea_mensaje_201.png)

*`POST /medical-sessions/{id}/messages` con el token del **Dr. Uno SR** — `201 Created`. Nótese que el body enviado solo contenía `body`, `messageType` y `pictogramId`; aun así, la respuesta incluye `"senderType": "staff"`, `"senderId"` con el UUID real del médico, `"senderName": "Dr. Uno SR"` y `"origin": "doctor"` — todo calculado por el backend a partir del token, nunca escrito por el cliente.*

![Canje del código para obtener el token de la paciente](capturas/75_chat_redeem_paciente_para_chat_200.png)

*`POST /auth/patient/redeem` — `200`, confirmando que el flujo de acceso construido en el Hito 5.0 interopera correctamente con el chat: el mismo `cta_code` de la atención permite a la paciente obtener un token acotado a esa conversación.*

![Mensaje enviado por la paciente](capturas/76_chat_paciente_crea_mensaje_201.png)

*`POST /medical-sessions/{id}/messages` con el token de la **paciente** — `201 Created`. La respuesta muestra `"senderType": "patient"`, `"senderId": null` (tal como exige el contrato) y `"senderName": "Paciente Prueba Dos"`. Nótese además `"confirmedByPatientAt"` con una fecha, no `null`: cuando el propio paciente envía un mensaje, queda auto-confirmado — no puede "no haber leído" lo que él mismo escribió.*

![Mensaje marcado como leído por el médico](capturas/77_chat_marcar_leido_200.png)

*`POST /messages/{id}/read` sobre el mensaje de la paciente, con el token del médico — `200`, con `"status": "read"`. Confirma que el personal de salud puede marcar como leídos los mensajes de la conversación.*

### 14.4 Hito 5.3 — Mensajes de sistema: pagando la deuda de Fase 4

**El problema.** Desde el diseño de la Fase 5 quedó declarada una deuda: los endpoints `PATCH /medical-sessions/{id}/stage` y `POST /medical-sessions/{id}/close` (construidos en Fase 4, Hito 3) debían insertar automáticamente un mensaje en el chat cada vez que la atención cambiara de etapa — pero `ChatMessage` no existía todavía en ese momento. Sin esto, el paciente vería su conversación congelada mientras, en la trastienda, su atención avanza de Admisión a Categorización a Consulta Médica.

**Resolución adoptada:** un servicio único, `SystemMessageService`, enganchado en los tres puntos donde una atención cambia de estado:

```php
SystemMessageService::create(
    $medicalSession,
    "La atencion avanzo a {$siguienteEtapa->label()}."
);
```

Reutiliza el método `label()` que ya existía en `MedicalSessionStatus` desde Fase 4, en vez de duplicar el texto de cada etapa como strings sueltos. Para el salto de emergencia, el mensaje es deliberadamente neutro (*"por criterio de emergencia"*), sin repetir el motivo clínico interno (`triage_skip_reason`) — ese detalle queda reservado al registro médico, no al chat del paciente.

**Verificación con evidencia real**, sobre la misma sesión ya usada en el Hito 5.2 (con mensajes previos de un médico y una paciente):

![Avance de etapa vía PATCH /stage](capturas/78_sysmsg_patch_stage_200.png)

*`PATCH /medical-sessions/{id}/stage` con un usuario de Admisión — `200`, la sesión avanzó de `in_admission` a `in_triage`.*

![Historial de mensajes tras el avance](capturas/79_sysmsg_get_messages_200.png)

*`GET /medical-sessions/{id}/messages` sobre la misma sesión inmediatamente después. Al final del historial, sin que nadie lo escribiera manualmente, apareció:*

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

*El mensaje quedó mezclado cronológicamente con los mensajes humanos anteriores del médico y la paciente, confirmando que la deuda de Fase 4 se pagó correctamente.*

### 14.5 Hito 5.4 — `Consent`: el sistema de consentimientos

**El problema.** El médico necesita poder solicitar un permiso al paciente (por ejemplo, compartir su información con un contacto de emergencia), pero **solo el paciente** puede decidir sobre su propia atención — ni siquiera quien solicitó el permiso, ni un `super_admin`, debe poder responder en su nombre.

**Máquina de estados con transiciones controladas en el modelo:**

```
pending ──approve──> granted ──revoke──> revoked
   │
   └──reject──> rejected
```

Cada transición (`approve`, `reject`, `revoke`) valida su propio estado de origen **dentro del modelo** `Consent`, no en el controlador — así, sin importar desde dónde se invoque, nunca es posible saltarse un paso de la máquina (por ejemplo, revocar algo que nunca se otorgó).

**Plantillas en vez de texto libre (D-08).** El título y la descripción de cada consentimiento se generan desde el enum `ConsentType` al momento de responder, nunca los escribe el médico a mano. Es una decisión de seguridad: si el texto fuera libre, un error de tipeo con dos contactos de apellido parecido podría autorizar el envío de datos clínicos a la persona equivocada.

**Verificación con evidencia real**, sobre la misma sesión usada en los hitos anteriores, con un médico y la paciente dueña de la atención:

![Solicitud de consentimiento creada por el médico](capturas/80_consent_solicitar_201.png)

*`POST /medical-sessions/{id}/consent-requests` con el token del **Dr. Uno SR** — `201 Created`. El `title` ("Inicio de la atención") y la `description` se generaron desde la plantilla del tipo `start_care`, sin que el médico escribiera ese texto.*

![El médico intenta aprobar su propia solicitud](capturas/81_consent_medico_aprobar_403.png)

*`POST /consent-requests/{id}/approve` con el **mismo token del médico** — `403 WRONG_TOKEN_TYPE`, `"Este recurso es exclusivo del paciente."` Esta es la evidencia central del hito: el middleware bloquea a quien solicitó el consentimiento antes de que la Policy siquiera se evalúe, confirmando en ejecución la regla de autonomía del paciente.*

![La paciente aprueba su propio consentimiento](capturas/82_consent_paciente_aprobar_200.png)

*`POST /consent-requests/{id}/approve` con el token de la **paciente** dueña de la atención — `200`, `"status": "granted"`, con `grantedAt` registrado.*

![La paciente revoca el consentimiento otorgado](capturas/83_consent_paciente_revocar_200.png)

*`POST /consent-requests/{id}/revoke` — `200`, `"status": "revoked"`, con `revokedAt` registrado. La máquina de estados completa (`pending → granted → revoked`) quedó verificada de punta a punta.*

### 14.6 Hito 5.5 — Cascada de cierre completa: el último hito de la fase

**El problema.** Cerrar una atención debía ser una única operación atómica, pero hasta este hito eran llamadas sueltas: se actualizaba el estado y se revocaba el token del paciente, y por separado se generaba el mensaje de sistema. Faltaban además dos efectos exigidos por el diseño de la fase: revocar los consentimientos que siguieran vigentes y expirar el CTA asociado. Sin esto, una atención podía cerrarse dejando "vivo" un permiso para compartir datos clínicos.

**Resolución adoptada:** el método `closeSession()` del modelo `MedicalSession` se reescribió para envolver cinco efectos dentro de una sola transacción — si cualquiera falla, PostgreSQL deshace todos:

```php
DB::transaction(function () use (...) {
    $this->update([...]);                       // estado de la sesión
    $this->patient->tokens()->delete();          // acceso del paciente
    $this->consents()->where('status', 'granted')->get()->each(...); // revoca consents
    $this->temporaryAccessCode?->update(['status' => 'expired']);     // expira el CTA
    SystemMessageService::create($this, 'La atencion fue cerrada.');  // deja constancia
});
```

Para "expirar" el CTA no fue necesario agregar ningún valor nuevo al enum: `'expired'` ya existía en `temporary_access_codes.status` desde el Hito 2 de Fase 4 — se reutilizó el mismo significado en un contexto distinto, evitando duplicar la semántica del estado.

**Verificación con evidencia real de la transacción completa.** Se preparó un consentimiento en estado `granted` sobre una atención ya usada en hitos anteriores, y se cerró vía `POST /medical-sessions/{id}/close`:

![Cierre de la atención](capturas/84_cierre_close_200.png)

*`POST /medical-sessions/{id}/close` — `200`, `"status": "closed"`, `"isWritable": false`.*

Inmediatamente después, se consultaron directamente en base de datos los tres efectos nuevos:

```
Consent   → status: "revoked"   | evidence: {"reason": "session_closed"} | revoked_at: 15:25:04
CTA       → status: "expired"
ChatMessage → sender_type: "system" | body: "La atencion fue cerrada." | sent_at: 15:25:04
```

*Nota especialmente relevante: los tres timestamps —`revoked_at` del consentimiento, `ended_at` de la sesión, y `sent_at` del mensaje de sistema— resultaron exactamente el mismo instante, confirmando que ocurrieron dentro de la misma transacción atómica y no como pasos secuenciales independientes.*

### 14.7 Estado de la Fase 5 — completa

| Hito | Contenido | Estado |
|---|---|---|
| 5.0 | Acceso del paciente — token derivado del CTA, segregación de identidad | ✅ |
| 5.1 | Catálogo de Pictogramas — categorías, severidad semántica, RBAC verificado | ✅ |
| 5.2 | `ChatMessage` — chat con derivación de identidad desde el backend, verificado con evidencia real | ✅ |
| 5.3 | Mensajes de sistema — retrofit de S4/S5 de Fase 4, verificado con evidencia real | ✅ |
| 5.4 | `Consent` — consentimientos con máquina de estados y autonomía del paciente, verificado con evidencia real | ✅ |
| 5.5 | Cascada de cierre completa — verificado con evidencia real de atomicidad | ✅ |

**Fase 5 — Chat y Consentimientos — completa.** Los seis hitos definidos están construidos, probados con evidencia HTTP real, y documentados.

---

## 15. Fase 6 — Administración (en progreso)

> 💡 **De qué trata esta fase.** La Fase 5 dejó el catálogo de pictogramas congelado a propósito: se necesitaba primero que el chat funcionara como consumidor de ese catálogo, antes de construir quién lo administra. La Fase 6 cierra esa deuda y agrega, en general, todo lo que un administrador institucional necesita para operar el sistema día a día sin tocar la base de datos directamente.

### 15.1 Hito 6.0 — CRUD completo de Pictogramas

**El problema.** Desde el Hito 5.1, el catálogo de pictogramas solo se podía leer y crear — no existía forma de editar parcialmente, desactivar, ni administrar categorías. Además, una revisión de código detectó que el único endpoint de edición (`PATCH /pictograms/{id}`) reutilizaba el `FormRequest` de creación, con reglas `required` en todos los campos — lo que rompe la semántica de una actualización parcial (`PATCH` según el estándar HTTP).

**Bugs encontrados y corregidos durante la construcción de este hito:**

| # | Bug | Causa | Corrección |
|---|---|---|---|
| 1 | `PATCH` exigía todos los campos | `update()` reutilizaba `StorePictogramRequest` (reglas `required`) | Se creó `UpdatePictogramRequest` con reglas `sometimes` |
| 2 | `TypeError` fatal si un paciente listaba pictogramas | `viewAny(User $user)` llevaba type-hint estricto, pero un `Patient` no es instancia de `User` | Se quitó el type-hint: `viewAny($user)` |
| 3 | `Call to undefined method ...::validate()` | Laravel 11+ ya no incluye el trait `ValidatesRequests` por defecto en el controller base | Se reemplazó por `Validator::make(...)->validate()` explícito |
| 4 | Swagger no mostraba el campo de body en varios endpoints | Faltaba la anotación `requestBody` en las rutas `PATCH`/`POST` de categorías | Se agregó `requestBody` a cada anotación `#[OA\...]` correspondiente |
| 5 | Una categoría nueva se creaba con `isActive: null` | `is_active` no estaba en `$fillable` del modelo `PictogramCategory` — Laravel descarta silenciosamente cualquier columna fuera de esa lista blanca | Se agregó `is_active` a `$fillable` y un `cast` a `boolean` |

Ninguno de estos bugs llegó a producción sin probarse: cada uno se detectó ejecutando el endpoint real desde Swagger, no leyendo el código en silencio.

**Endpoints nuevos construidos:**

| Endpoint | Verbo | Novedad |
|---|---|---|
| `/pictograms` | `GET` | Ahora soporta `search`, `sort` (con desempate determinista por `id`) e `includeInactive` |
| `/pictograms/{id}` | `PATCH` | Corregido: actualización realmente parcial |
| `/pictograms/{id}` | `DELETE` | Nuevo — desactiva (`is_active = false`), nunca borra de la base de datos |
| `/pictograms/{id}/restore` | `PATCH` | Nuevo — reactiva |
| `/pictogram-categories` | `POST` | Nuevo — crear categoría |
| `/pictogram-categories/{id}` | `PATCH` | Nuevo — editar parcialmente |
| `/pictogram-categories/{id}` | `DELETE` | Nuevo — desactivar |
| `/pictogram-categories/{id}/restore` | `PATCH` | Nuevo — reactivar |

**Por qué "eliminar" es desactivar y no borrar.** Un pictograma o una categoría puede estar referenciado por mensajes de chat de atenciones ya cerradas. Borrar la fila de la base de datos dejaría esas referencias rotas — el historial clínico-legal de una atención no puede mostrar "pictograma eliminado". Se aplicó el mismo criterio ya usado en Organizaciones, Centros y Unidades desde la Fase 4: `is_active = false` en vez de `DELETE FROM`.

**Evidencia de ejecución real, desde Swagger:**

![Creación de un pictograma](capturas/85_pictograms_crear_201.png)

*`POST /pictograms` con el token de `admin_institucional` — `201 Created`. Pictograma de prueba, eliminado tras la verificación.*

![Actualización parcial real](capturas/86_pictograms_patch_parcial_200.png)

*`PATCH /pictograms/{id}` enviando **únicamente** `{"isActive": false}` — `200 OK`, sin exigir `title`, `phrase`, `emoji` ni el resto de los campos. Esta es la evidencia central del hito: confirma en ejecución que el bug de la actualización parcial quedó corregido.*

![Búsqueda por texto](capturas/87_pictograms_search_dolor_200.png)

*`GET /pictograms?search=dolor` — devuelve únicamente los pictogramas cuyo título o frase contienen el término, excluyendo automáticamente los inactivos.*

![Bloqueo de rol para Super Admin](capturas/88_pictograms_crear_forbidden_super_admin_403.png)

*`POST /pictograms` con el token de **Super Admin** — `403 FORBIDDEN_ROLE`. Confirma, igual que en el Hito 5.1, que la gestión del catálogo es exclusiva de `admin_institucional`.*

![Validación de nombre duplicado en categorías](capturas/89_pictogram_categories_duplicada_422.png)

*`POST /pictogram-categories` con un nombre ya existente ("Dolor") — `422 Unprocessable Content`, regla `unique` aplicada correctamente.*

![Creación de una categoría](capturas/90_pictogram_categories_crear_201.png)

*`POST /pictogram-categories` — `201 Created` con `isActive: true`. Esta captura corresponde a la ejecución **posterior** a la corrección del bug 5 (`$fillable`); antes de la corrección, el mismo endpoint devolvía `isActive: null`.*

![Desactivación de una categoría](capturas/91_pictogram_categories_desactivar_200.png)

*`DELETE /pictogram-categories/{id}` — `200 OK`, `isActive: false`. La categoría no se borra de la base de datos.*

![Reactivación de una categoría](capturas/92_pictogram_categories_restaurar_200.png)

*`PATCH /pictogram-categories/{id}/restore` — `200 OK`, `isActive: true`. Ciclo completo de desactivar/reactivar verificado.*

**Estado del catálogo tras la verificación:** los registros de prueba ("Dolor de prueba", "Categoria de prueba") fueron eliminados definitivamente por Tinker una vez concluidas las pruebas, dejando el catálogo en su estado real de **9 pictogramas y 4 categorías**, verificado con:

```php
Pictogram::count(); // 9
PictogramCategory::count(); // 4
```

### 15.2 Hito 6.1 — Gestión de usuarios (corrección de escalación de privilegios)

**El problema encontrado — vulnerabilidad crítica.** El endpoint `POST /users`, ya construido desde la Fase 0, validaba que el `role` solicitado fuera uno de los cinco valores válidos del sistema, pero **no verificaba si quien hacía la petición tenía permiso para otorgar ese rol en particular**. Solo se comprobaba que el `healthCenterId` correspondiera al propio centro del `admin_institucional`. La consecuencia: cualquier `admin_institucional`, con una cuenta legítima de su propio hospital, podía enviar `"role": "super_admin"` y crear una cuenta con el nivel de privilegio más alto de todo el sistema — o editar su propia cuenta para auto-ascenderse por el mismo camino.

Esta categoría de vulnerabilidad está señalada explícitamente en `BACKEND_IMPLEMENTATION_GUIDE.md` §9.3 como **"Riesgo de escalamiento de privilegios (🔴 crítico)"**.

**La corrección adoptada — roles estructurales vs. roles operativos.** Se estableció una separación de dos niveles, coherente con la que ya existía para pictogramas (Hito 5.1): `super_admin` y `admin_institucional` son roles **estructurales** (gestionan la plataforma); `admision`, `categorizacion` y `medico` son roles **operativos** (hacen el trabajo clínico diario). Un `admin_institucional` puede otorgar únicamente roles operativos; un `super_admin` puede otorgar cualquiera. La regla se implementó en la capa de validación (`StoreUserRequest`/`UpdateUserRequest`), calculando dinámicamente la lista de roles permitidos según quien hace la petición — no en la Policy, que solo decide si la acción en general está permitida, no qué valor de dato es aceptable.

**Segunda protección — nadie cambia su propio rol.** Además de restringir qué roles se pueden otorgar, se bloqueó explícitamente que un usuario modifique su propio campo `role` desde `PUT /users/{id}`, cerrando la puerta de auto-ascenso incluso dentro de los límites de rol ya permitidos.

**Evidencia de ejecución real, desde Swagger:**

![Escalación de privilegios bloqueada](capturas/93_users_escalacion_bloqueada_422.png)

*`POST /users` con el token de `admin_institucional`, solicitando `"role": "super_admin"` — `422 Unprocessable Content`, `"No tienes permiso para asignar ese rol."`. Esta es la evidencia central del hito: confirma en ejecución que la vulnerabilidad de escalación de privilegios quedó cerrada.*

![Rol operativo permitido](capturas/94_users_rol_operativo_permitido_201.png)

*Mismo endpoint, mismo `admin_institucional`, solicitando `"role": "medico"` — `201 Created`. Confirma que la restricción es específica al valor del rol, no un bloqueo general de la creación de usuarios.*

![Auto-cambio de rol bloqueado](capturas/95_users_auto_cambio_rol_bloqueado_422.png)

*`PUT /users/{id}` donde el `admin_institucional` intenta cambiar su **propio** rol a `super_admin` — `422`, con los dos mensajes de validación combinados: rol no autorizado para su nivel, y prohibición de auto-modificación.*

![Edición de rol de otra persona permitida](capturas/96_users_editar_rol_otro_permitido_200.png)

*`PUT /users/{id}` sobre **otro** usuario (no el propio admin), cambiando su rol a `categorizacion` — `200 OK`. Confirma que la protección de auto-modificación no bloquea la gestión legítima de terceros.*

**Paginación y filtros del listado, según contrato §14.6 y §15.4.** `GET /users` ahora responde con el envoltorio `meta.pagination` (total, página actual, última página) y admite filtros por `role`, `healthCenterId`, `unitId`, `isActive`, además de `sort` (por defecto `name`). Verificado con `GET /users` (7 usuarios del propio centro, paginados de a 25) y `GET /users?role=medico` (2 resultados, ambos con `role: "medico"`).

**Estado tras la verificación:** el usuario de prueba creado durante las pruebas (`enfermero.valido.prueba@test.com`) fue eliminado por Tinker al finalizar.

### 15.3 Hito 6.2 — Parámetros de seguridad (`security_settings`)

**El problema.** El límite de intentos fallidos del CTA estaba escrito directamente en el código (`'max_attempts' => 3`, un número fijo), en vez de ser una configuración que cada centro de salud pudiera ajustar según su propia política de seguridad. La decisión de diseño D-14 (registrada al planear la Fase 6) estableció que estos parámetros deben ser **por centro de salud**, no globales ni por organización — coherente con que el resto del sistema ya aplica multitenancy por centro.

**Alcance deliberadamente acotado.** Se evaluó incluir también un campo `session_timeout_minutes`, pero se descartó para este hito: no existe hoy ninguna lógica en el sistema que revise el tiempo de inactividad de una atención para cerrarla automáticamente. Construir el campo sin esa lógica habría producido una configuración decorativa — el administrador cambiaría un número creyendo que protege algo, sin ningún efecto real. Se prefirió entregar un solo parámetro **completamente funcional** (`cta_max_attempts`) antes que dos, uno de ellos hueco. El timeout de sesión queda para un hito posterior, junto con el mecanismo que lo consuma.

**Diseño de la conexión real.** El valor no se lee en tiempo real cada vez que se valida un código: se copia al propio CTA en el momento en que se genera. Esto preserva una decisión de diseño ya existente desde la Fase 4 — cada código conserva el límite con el que nació, así que un cambio posterior en la configuración nunca altera retroactivamente códigos ya emitidos.

**Bug encontrado durante la verificación — orden de autorización.** El endpoint `GET /security-settings` intentaba crear el registro de configuración (`firstOrCreate`) *antes* de verificar el rol de quien preguntaba. Como `super_admin` es un rol libre sin centro asociado (`health_center_id` es `null` para esa cuenta), el intento de inserción violaba la restricción `NOT NULL` de la columna a nivel de base de datos, produciendo un `500 Internal Server Error` en vez de un `403` controlado. Se corrigió invirtiendo el orden: primero se verifica el rol, y solo si corresponde se toca la base de datos.

**Evidencia de ejecución real, desde Swagger:**

![Configuración por defecto autocreada](capturas/97_security_settings_default_200.png)

*`GET /security-settings` en la primera consulta del centro — `200 OK` con `ctaMaxAttempts: 3`, el valor por defecto, creado automáticamente sin que nadie tuviera que inicializarlo a mano.*

![Actualización del límite](capturas/98_security_settings_actualizar_200.png)

*`PUT /security-settings` cambiando el límite a `5` — `200 OK`, mismo `id` de registro, confirmando que actualizó la fila existente en vez de crear una nueva.*

![CTA generado tras el cambio](capturas/99_cta_generado_201.png)

*`POST /patients/{id}/attention-codes` (endpoint público) generando un código nuevo después del cambio de configuración.*

Verificado directamente en la base de datos vía Tinker que este código nació con `max_attempts = 5` — el valor configurado, no el `3` que tenía el código antes de escribir esta conexión. Esta es la evidencia central del hito: confirma que la configuración no es solo un valor que se guarda, sino que efectivamente altera el comportamiento del sistema.

![Bug encontrado: 500 en vez de 403](capturas/100_security_settings_bug_500_super_admin.png)

*`GET /security-settings` con el token de `super_admin`, **antes** de la corrección — `500 Internal Server Error` por violación de restricción `NOT NULL`, en vez del `403` que correspondía. Se documenta el bug junto con su corrección como parte del proceso real de desarrollo.*

![Bug corregido: 403 limpio](capturas/101_security_settings_forbidden_403.png)

*Mismo endpoint, mismo token de `super_admin`, después de invertir el orden de verificación — `403 FORBIDDEN_ROLE`, respuesta controlada y consistente con el resto del sistema.*

![Validación de rango](capturas/102_security_settings_rango_422.png)

*`PUT /security-settings` con `ctaMaxAttempts: 15`, fuera del rango permitido (1–10) — `422 Unprocessable Content`. Evita que un centro quede con una protección contra fuerza bruta anulada por un valor absurdo.*

**Estado tras la verificación:** la configuración del centro de prueba fue restaurada a `cta_max_attempts: 3` (su valor original) por Tinker al finalizar.

### 15.4 Hito 6.3 — Consulta de auditoría (`audit-logs`)

**El punto de partida.** La tabla `audit_logs` y el `AuditLogObserver` existían desde la Fase 0, registrando eventos automáticamente sobre `HealthCenter`, `Organization`, `Patient`, `PatientContact`, `TemporaryAccessCode`, `Unit` y `User`. Faltaba, sin embargo, el endpoint de consulta — y una revisión contra el contrato reveló dos brechas más profundas antes de poder construirlo con honestidad.

**Brecha 1 — campos obligatorios del contrato ausentes.** El contrato (§19.2) exige `healthCenterId` (aislamiento por centro) y `severity` (`info`/`warning`/`critical`) como campos obligatorios de todo evento de auditoría. Ninguno existía en la tabla. Sin `healthCenterId`, un `admin_institucional` habría podido ver la actividad de **todos** los hospitales del sistema al consultar la bitácora — una fuga de datos entre centros, exactamente lo que el resto del proyecto se cuida de evitar.

**Brecha 2 — modelos sensibles sin auditar.** `Pictogram`, `PictogramCategory`, `SecuritySetting` y `Consent` no estaban conectados al observador. La revocación de un consentimiento, que el contrato marca explícitamente como evento crítico de auditoría (§13.7), no dejaba ningún rastro.

**Decisión de diseño — clasificación de severidad con criterio real.** En vez de asignar `severity: 'info'` de forma fija a todo evento (lo que habría sido, otra vez, una función decorativa), se implementó una regla de negocio centralizada en el propio `AuditLogObserver`:

| Condición | Severidad |
|---|---|
| Un `Consent` cambia su estado a `revoked` | `critical` — exigencia explícita del contrato |
| Un `TemporaryAccessCode` se bloquea por intentos fallidos | `warning` |
| Se modifica (no se crea) un modelo estructural: `User`, `Organization`, `HealthCenter`, `Unit`, `SecuritySetting` | `warning` |
| Cualquier otro evento | `info` |

**Decisión de diseño — cómo se deriva el centro de cada evento.** Como el contrato limita el acceso a la bitácora exclusivamente a `admin_institucional` (ni siquiera `super_admin` accede, §13.7), lo relevante es "qué hizo mi propio personal, o qué le pasó a mis propios pacientes" — no perseguir una relación distinta por cada tabla. Se implementó una resolución en cascada: primero se usa la columna `health_center_id` propia del modelo si existe; si no, se hereda de la `MedicalSession` asociada (caso de `Consent`); como último recurso, se usa el centro del actor que ejecuta la acción.

**Bug encontrado durante la verificación — `Auth::id()` falla con pacientes.** Al probar la revocación de un consentimiento (acción que ejecuta el propio paciente, no el personal), el observador lanzó un `500 Internal Server Error`: `Auth::id()` invoca `getAuthIdentifier()`, método que el modelo `Patient` no implementa por completo. La corrección obtiene el actor de forma segura, verificando su tipo antes de leer su `id`, de modo que un evento disparado por un paciente se registra igual, simplemente sin `userId` asociado.

**Evidencia de ejecución real, desde Swagger:**

![Pictograma creado, evento generado](capturas/103_pictogram_creado_evento_generado_201.png)

*`POST /pictograms` — `201 Created`. Este evento alimenta la siguiente prueba.*

![Evento info visible en la bitácora](capturas/104_audit_logs_evento_info_200.png)

*`GET /audit-logs` — el evento de creación del pictograma aparece con `severity: "info"`, `resourceType: "Pictogram"` y el nombre real del administrador que lo creó.*

![Consentimiento revocado por el paciente](capturas/105_consent_revocado_por_paciente_200.png)

*`POST /consent-requests/{id}/revoke`, ejecutado con un token de **paciente** — `200 OK`. Esta es la acción que expuso el bug de `Auth::id()`, corregido antes de esta captura.*

![Aislamiento por centro: el filtro no traspasa hospitales](capturas/106_audit_logs_aislamiento_centro_incorrecto_vacio.png)

*`GET /audit-logs?severity=critical` consultado por el admin de un centro **distinto** al del consentimiento revocado — `total: 0`. Confirma que el filtro implícito de aislamiento por centro nunca se puede saltar, ni siquiera con un filtro explícito válido.*

![Evento crítico visible para el centro correcto](capturas/107_audit_logs_evento_critical_centro_correcto_200.png)

*El mismo filtro, consultado por el admin del centro correcto — `total: 1`, `severity: "critical"`, `action: "updated"`, `resourceType: "Consent"`. `userId: null` porque el actor fue un paciente, no un miembro del personal — comportamiento esperado tras la corrección del bug.*

**Limitación conocida, documentada explícitamente.** El contrato (§15.4) sugiere un filtro `patientId` para `/audit-logs`, que no se implementó: el esquema actual de `audit_logs` vincula el evento a su entidad mediante `auditable_type`/`auditable_id` (relación polimórfica), sin una columna directa de paciente. Añadir ese filtro requeriría cambios de esquema adicionales, fuera del alcance de este hito.

**Estado tras la verificación:** el pictograma y los dos consentimientos de prueba fueron eliminados por Tinker al finalizar; el token de paciente generado manualmente para la prueba fue revocado.

### 15.5 Hito 6.4 — `/admin/stats` y exportación firmada de logs

**El problema declarado por el propio contrato.** El requisito funcional RF-012 es inusualmente honesto: *"Las tres cifras están escritas a mano en el código y nunca cambian."* El hito exigía calcular tres valores reales — `activeUsers`, `apiRequests`, `auditCoverage` — donde ninguno existía como número calculado.

**`activeUsers`** se resolvió de forma directa: conteo de usuarios activos, acotado al centro del admin, mismo patrón de aislamiento usado en toda la Fase 6.

**`apiRequests` requirió infraestructura nueva.** No existía ningún mecanismo que contara peticiones a la API. Se construyó un middleware (`TrackApiRequests`) que incrementa un contador en caché (ventana de 24 horas) en cada petición. Una decisión de diseño relevante: el middleware se colocó **antes** de la autenticación (`prepend`, no `append`), para que el conteo refleje tráfico real — incluidas peticiones rechazadas por falta de token, que son justamente las que interesa poder ver en volumen (por ejemplo, ante un intento de fuerza bruta).

**`auditCoverage` se calculó con reflexión de PHP, no como cifra fija.** En vez de escribir `100` a mano, el sistema inspecciona en tiempo real, usando `ReflectionClass`, qué porcentaje de los modelos considerados sensibles tienen efectivamente el atributo `#[ObservedBy(AuditLogObserver::class)]`. Esto convierte el número en una auto-verificación genuina: si en el futuro se agrega un modelo sensible y alguien olvida conectar el observador, la cifra baja automáticamente por debajo de 100, en vez de mentir silenciosamente.

**Hueco encontrado durante el diseño de esa métrica.** Calcular `auditCoverage` con honestidad exigió primero auditar qué modelos *no* estaban cubiertos. Se encontró que `MedicalSession` — descrita en el propio contrato como *"raíz de todo el dominio clínico"* — nunca había sido conectada al observador, pese a que el contrato lista explícitamente como acciones auditables iniciar, avanzar y cerrar una atención. Se conectó antes de calcular la métrica, para que el número resultante fuera real y no artificialmente inflado por omisión.

**Segundo requisito del contrato, encontrado en la relectura de §13.7.** *"La consulta de la bitácora también se audita."* No solo modificar datos: **mirarlos** también debe dejar rastro. Como una simple lectura no dispara ningún evento de Eloquent, se agregó un registro manual (`viewed_audit_log`, `exported_audit_log`) directamente en el controlador, para las dos acciones de acceso a la bitácora.

**Exportación firmada — decisión D-30 implementada.** Se generó una clave de firma (`AUDIT_EXPORT_SIGNING_KEY`, 32 bytes aleatorios) separada de `APP_KEY`, para que una eventual rotación de esta última no invalide silenciosamente firmas de exportaciones ya emitidas. El endpoint `POST /audit-logs/export` serializa el conjunto completo de eventos del centro en un JSON de bytes fijos y calcula sobre ellos un HMAC-SHA256. La firma con certificado digital, de mayor peso legal, queda explícitamente diferida a una fase posterior — documentado como decisión académica, no como funcionalidad ausente por descuido.

**Verificación rigurosa de la firma.** Se capturó la respuesta real del servidor byte por byte (sin copiar y pegar manualmente, para no introducir diferencias de formato) y se recalculó el HMAC de forma completamente independiente, en un script PHP separado que lee el archivo exportado y usa la misma clave de configuración. El resultado coincidió exactamente:

```
Firma recalculada: 782a3e8429717e0474c9e3dc24fc569b02e13f43a956baa09cc080df471d506f
Firma del archivo: 782a3e8429717e0474c9e3dc24fc569b02e13f43a956baa09cc080df471d506f
Coinciden: SI
```

Esta prueba es la evidencia central del hito: demuestra que la firma no es un campo decorativo en la respuesta, sino un mecanismo criptográfico verificable de forma independiente al propio servidor que la generó.

**Evidencia de ejecución real, desde Swagger:**

![Estadísticas calculadas en vivo](capturas/108_admin_stats_calculadas_200.png)

*`GET /admin/stats` — `200 OK` con `activeUsers`, `apiRequests` y `auditCoverage: 100`, ninguno escrito a mano.*

![El contador de peticiones incrementa con el uso real](capturas/109_admin_stats_contador_incrementa_200.png)

*Segunda consulta al mismo endpoint — `apiRequests` subió respecto a la captura anterior, confirmando que el middleware cuenta tráfico real en cada petición, no un valor congelado.*

![Bloqueo de rol para Super Admin](capturas/110_admin_stats_forbidden_super_admin_403.png)

*`GET /admin/stats` con el token de `super_admin` — `403 FORBIDDEN_ROLE`. Estas estadísticas son operación de un centro específico, exclusivas de `admin_institucional`.*

![Exportación firmada](capturas/111_audit_logs_export_firmado_200.png)

*`POST /audit-logs/export` — `200 OK` con el payload completo, `signatureAlgo: "HMAC-SHA256"` y la firma calculada. La verificación independiente de esta firma se documenta arriba.*

**Estado tras la verificación:** los archivos temporales usados para la verificación de la firma (`export_test.json`, `verify_signature.php`) fueron eliminados del entorno local al finalizar. Los registros de auditoría generados durante las pruebas de este hito **no fueron eliminados**: el contrato exige que la bitácora sea inmutable — solo escritura y lectura —, por lo que forman parte del historial real del sistema, igual que en producción.

---

**Cierre de la Fase 6 — Administración.** Con este hito se completan los cinco pilares planeados: mantenimiento de catálogos (6.0), gestión de usuarios con jerarquía de roles protegida (6.1), configuración de seguridad conectada en tiempo real (6.2), bitácora de auditoría con clasificación de severidad y aislamiento por centro (6.3), y estadísticas verificables más exportación firmada (6.4). A lo largo de la fase se encontraron y corrigieron 9 bugs reales — varios de ellos de seguridad (escalación de privilegios, fallas de autorización que exponían errores de servidor) — todos verificados con evidencia HTTP antes y después de la corrección.

---

## 16. Cumplimiento de la rúbrica

Esta tabla resume cómo cada indicador de la rúbrica queda cubierto por las evidencias de este informe.

| Indicador de la rúbrica | Puntos | Evidencia en este informe | Estado |
|---|---|---|---|
| **1.** Conexión BD + configuración en `.env` + modelos | 33 | Sección 2 (`.env`, `about`, `migrate:status`) + Sección 5.1–5.7 (creación y listado de catálogos) + Sección 10 (estado actualizado) | ✅ |
| **2.** Login + middleware de autenticación | 34 | Sección 3 (login, `/me`, logout, rate limiting) + Sección 6.4 (ciclo completo desde Swagger) | ✅ |
| **3.** Registro de usuario con cifrado de contraseña | 33 | Sección 4 (registro 201 + hash `$2y$12$` en Tinker + control de duplicados) | ✅ |
| **TOTAL** | **100** | | ✅ |

### Evidencia adicional no exigida por la rúbrica

El trabajo documentado en este informe excede los tres indicadores mínimos. Las siguientes secciones documentan capas construidas después de la entrega base:

| Sección | Aporte |
|---|---|
| **5.4** | Control de acceso basado en roles (RBAC) sobre el registro de usuarios |
| **5.8** | Aislamiento por *multitenancy* entre centros de salud |
| **6** | Documentación OpenAPI 3.0 interactiva, generada desde el código |
| **7** | CRUD completo con borrado lógico y verificación de persistencia |
| **8** | Modelo de Paciente con autorregistro público y edad derivada |
| **9** | Código Temporal de Atención con hash, expiración, uso único y límite de frecuencia |
| **10** | Modelo de Sesión Médica: 5 endpoints, Policy por rol/unidad/etapa, salto de emergencia auditado |
| **11** | Manejo unificado de errores con código legible por máquina, y corrección de una brecha de acceso detectada |
| **12** | Verificación del esquema completo en PostgreSQL |
| **13** | Índice único parcial, transacciones y análisis de dos errores de implementación |

---

## 17. Glosario rápido

Para quien lea este informe sin ser parte del proyecto (por ejemplo, un evaluador que quiera repasar los términos técnicos):

| Término | En palabras simples |
|---|---|
| **API REST** | Una forma estándar de organizar un backend para que hable en JSON con cualquier frontend, a través de rutas como `/api/v1/...` |
| **Endpoint** | Una "puerta" específica de la API — por ejemplo, `POST /api/v1/auth/login` es el endpoint del login |
| **Token Bearer** | Una especie de carnet digital temporal que el servidor entrega al hacer login, y que hay que "mostrar" (enviar en el header `Authorization`) en cada petición siguiente para probar quién eres |
| **Sanctum** | El paquete de Laravel que genera, valida y revoca esos tokens |
| **Hash (bcrypt)** | El resultado de cifrar un texto de forma irreversible — no se puede "descifrar" de vuelta al original, solo comparar si otro texto genera el mismo hash |
| **Salt** | Un valor aleatorio que bcrypt añade antes de cifrar, y que hace que el mismo texto produzca un hash distinto cada vez. Es lo que impide buscar directamente por hash en la base de datos |
| **Rate limiting** | Un límite de cuántas veces se puede intentar algo en un período de tiempo, para frenar ataques de fuerza bruta |
| **RBAC** (*Role-Based Access Control*) | Control de acceso basado en el rol del usuario: no todos los usuarios autenticados pueden hacer todo |
| **Multitenancy** | Que un mismo sistema sirva a varios "inquilinos" (aquí, hospitales) manteniendo sus datos completamente separados entre sí |
| **Migración** | Un archivo de código que describe cómo crear o modificar una tabla, de forma que el esquema completo se pueda reconstruir en cualquier máquina |
| **Batch** (en migraciones) | El número de grupo bajo el que Laravel registra las migraciones aplicadas en una misma ejecución |
| **Tinker** | La consola interactiva de Laravel para ejecutar código PHP directamente contra el proyecto y su base de datos |
| **Swagger / OpenAPI** | Un estándar para describir APIs, y la interfaz web que permite leer y **ejecutar** esos endpoints desde el navegador |
| **Policy** | Una clase de Laravel que concentra las reglas de "quién puede hacer qué" sobre una entidad, separadas del controlador |
| **Borrado lógico** (*soft delete*) | Marcar un registro como inactivo en vez de eliminarlo físicamente, preservando el historial y las relaciones |
| **Accessor** | Un método del modelo que calcula un valor al momento de leerlo, en vez de guardarlo en la base de datos — como la edad derivada de la fecha de nacimiento |
| **CTA** | Código Temporal de Atención. Credencial intransferible y de un solo uso con la que un paciente se identifica ante Admisión |
| **Sesión médica** | La atención clínica concreta de un paciente. **No** es lo mismo que la sesión de usuario del personal |
| **Enum** (de PHP) | Una lista cerrada de valores posibles. Si escribes uno mal, el lenguaje avisa antes de ejecutar, en vez de fallar en producción |
| **Índice único parcial** | Restricción de unicidad de PostgreSQL que solo aplica a las filas que cumplen una condición. Permite «un paciente, una atención **abierta**» sin bloquear sus visitas futuras |
| **Transacción** | Un grupo de escrituras que ocurren **todas o ninguna**. Si algo falla a mitad, la base de datos deshace lo hecho |
| **Middleware** | Capa que intercepta la petición **antes** del controlador. `auth:sanctum` es uno; `session.active` es otro, creado en este proyecto |
| **Route Model Binding** | Función de Laravel que convierte el `{id}` de la URL directamente en el objeto ya cargado desde la base de datos |
| **API Resource** | Capa que traduce el modelo (formato de base de datos) al JSON que espera el frontend: `camelCase`, campos calculados, relaciones resueltas |
| **`$fillable`** | Lista blanca de campos que un modelo acepta al crear o actualizar. Protege contra *mass assignment*; el costo es que un campo olvidado se descarta en silencio |
| **Salto de emergencia** | Omisión deliberada de la Categorización por riesgo vital. Solo `medico`, con motivo obligatorio y auditoría permanente |
| **Código de error** (`code`) | Identificador fijo y legible por máquina que acompaña a cada error. El frontend ramifica por él, nunca por el texto del mensaje |

---

<p align="center">
  <sub>Informe de evidencias — Proyecto <strong>SeñaVida</strong> · Backend API REST · Instituto Profesional San Sebastián</sub><br/>
  <sub>Secciones 1–5: entrega EVA2 · Secciones 6–13: Fase 4 completa (Hitos 1–4) · Sección 14: Fase 5 completa (Hitos 5.0–5.5) · Siguiente: Fase 6 — Administración</sub>
</p>
