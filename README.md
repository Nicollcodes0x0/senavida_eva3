# SEÑAVIDA — Evaluación 3 (Frontend + Backend Integrados)

Plataforma web de comunicación inclusiva para pacientes sordos en contextos de salud. Este repositorio contiene el frontend (React + Vite) y el backend (Laravel + PostgreSQL) como dos proyectos independientes que se comunican vía API REST.

```
senavida_eva3/
├── backend/    # API Laravel (PHP 8.4+, PostgreSQL, Sanctum)
└── frontend/   # SPA React (Vite, TypeScript, Tailwind CSS)
```

Documentación técnica detallada del frontend (arquitectura, sistema de diseño, estado de integración): [`frontend/DOCUMENTACION_FRONTEND.md`](./frontend/DOCUMENTACION_FRONTEND.md).

---

## Integrantes

- **Nicol Orellana** — Frontend e integración
- **Greudy Inoa** — Backend
- **Camila Rojo** — Backend

---

## Funcionalidades principales

- **Autenticación real** con Laravel Sanctum: login, sesión persistente, logout con revocación de token.
- **Control de acceso por rol** (`super_admin`, `admin_institucional`, `admisión`, `categorización`, `médico`, `paciente`), aplicado con Policies de Laravel — no solo ocultando botones en el frontend.
- **CRUD completo, visible en pantalla**, para dos entidades:
  - **Catálogos institucionales** (Organización → Centro de Salud → Unidad): crear, listar, editar, desactivar (soft delete) y restaurar.
  - **Usuarios/funcionarios**: registrar, listar, editar, desactivar y restaurar.
- **Estadísticas del panel de administración calculadas en vivo** por el backend (usuarios activos, peticiones a la API, cobertura de auditoría) — no son valores fijos.
- **Reglas de negocio validadas por el servidor**: por ejemplo, no se puede desactivar una organización que todavía tiene centros de salud activos (responde `409 Conflict`).

---

## Requisitos previos

- **PHP 8.4 o superior**, con las extensiones `pdo_pgsql`, `pgsql`, `mbstring`, `fileinfo` y `openssl` habilitadas. Se recomienda la variante **Non Thread Safe (NTS)** si se usa el servidor de desarrollo integrado de PHP en Windows.
- **Composer**
- **PostgreSQL** (servidor corriendo localmente, con un usuario y contraseña configurados)
- **Node.js** (versión compatible con Vite 7) y **npm**
- **Git**

---

## 1. Clonar el repositorio

```bash
git clone https://github.com/Nicollcodes0x0/senavida_eva3.git
cd senavida_eva3
```

---

## 2. Levantar el backend

```bash
cd backend
composer install
copy .env.example .env
php artisan key:generate
```

Edita el archivo `backend/.env` recién creado y configura la conexión a tu base de datos PostgreSQL:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=senavida
DB_USERNAME=postgres
DB_PASSWORD=tu_contraseña_de_postgres
```

Crea las tablas. Se recomienda `migrate:fresh` la primera vez, ya que el proyecto incluye varias migraciones (usuarios, catálogos, pacientes, sesiones médicas, pictogramas, chat, consentimientos, auditoría, configuración de seguridad):

```bash
php artisan migrate:fresh
php artisan db:seed
```

Levanta el servidor:

```bash
php artisan serve
```

El backend queda disponible en `http://127.0.0.1:8000`.

---

## 3. Levantar el frontend

En otra terminal:

```bash
cd frontend
npm install
```

Crea un archivo `frontend/.env` (en la raíz de `frontend/`, al mismo nivel que `package.json`) con:

```
VITE_API_URL=http://localhost:8000
```

Levanta el servidor de desarrollo:

```bash
npm run dev
```

El frontend queda disponible en `http://localhost:5173`.

> **Importante:** el backend solo permite peticiones desde `http://localhost:5173` (configurado en `backend/config/cors.php`). Si Vite levanta en otro puerto (por ejemplo porque el 5173 ya está en uso por otro proceso), la aplicación no podrá comunicarse con la API. Verifica que no haya otro proceso usando ese puerto antes de levantar el frontend.

---

## 4. Usuarios de prueba

El seeder crea una organización, un centro de salud, una unidad, y un usuario de prueba por cada rol del sistema. Todos comparten la misma contraseña.

| Correo | Rol | Contraseña |
|---|---|---|
| `super_admin@test.com` | Super Administrador | `password123` |
| `admin_institucional@test.com` | Administrador Institucional | `password123` |
| `admision@test.com` | Admisión / Ventanilla | `password123` |
| `categorizacion@test.com` | Categorización (TENS) | `password123` |
| `medico@test.com` | Médico | `password123` |

**Recomendación para revisión:**
- **`admin_institucional@test.com`** permite ver el CRUD completo de Catálogos y Usuarios, con las restricciones reales de su rol (por ejemplo, solo puede editar Unidades de su propio centro, no Organizaciones ni Centros de Salud).
- **`super_admin@test.com`** permite ver el mismo panel sin esas restricciones — puede editar cualquier Organización, Centro de Salud o Unidad.

---

## 5. Notas de permisos por rol (Policies de Laravel)

| Acción | `super_admin` | `admin_institucional` |
|---|---|---|
| Ver organizaciones/centros/unidades | ✅ | ✅ |
| Crear/editar/desactivar Organización | ✅ | ❌ |
| Crear/editar/desactivar Centro de Salud | ✅ | ❌ |
| Crear/editar/desactivar Unidad | ✅ | ✅ (solo dentro de su propio centro) |
| Registrar/editar/desactivar Usuarios | ✅ | ✅ (solo dentro de su propio centro) |

Un intento de acción fuera de estos permisos devuelve `403 Forbidden` desde el backend, con el mensaje correspondiente.

---

## 6. Estado de la integración

Un resumen detallado de qué módulos están conectados a la API real y cuáles todavía dependen de datos de ejemplo está disponible en la sección 7 de [`frontend/DOCUMENTACION_FRONTEND.md`](./frontend/DOCUMENTACION_FRONTEND.md). En resumen: autenticación, catálogos y usuarios están completamente integrados; el dominio clínico (sesiones médicas, chat, consentimientos, pictogramas) tiene backend completo pero su interfaz visual sigue pendiente de conectar.