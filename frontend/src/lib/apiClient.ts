/**
 * apiClient.ts
 *
 * Este archivo es el ÚNICO lugar del frontend donde hablamos directamente con
 * el backend de Laravel. Toda pantalla que necesite datos reales (login,
 * catálogos, usuarios, etc.) pasa por acá en vez de que cada componente haga
 * su propio fetch().
 *
 * Por qué lo organizamos así, centralizado:
 * - Si mañana cambia la URL del backend, la tocamos en un solo lugar
 *   (variable de entorno VITE_API_URL), no en cada componente.
 * - El token de sesión (Bearer de Sanctum) se agrega automáticamente en cada
 *   petición que lo necesita, sin que cada pantalla tenga que acordarse.
 * - El manejo de errores queda consistente: todos los componentes reciben
 *   los errores en la misma forma, sin importar qué endpoint hayan llamado.
 *
 * Sobre el formato de las respuestas del backend:
 * Antes de escribir esto revisamos directamente el código de
 * AuthController.php (no solo la documentación) para asegurarnos de qué
 * forma tienen las respuestas de verdad. El backend devuelve
 * { success: true, data } cuando todo sale bien, y
 * { success: false, error: { message } } cuando hay un error general
 * (credenciales incorrectas, cuenta desactivada, rate limiting). Los errores
 * de validación (422) usan el formato nativo de Laravel:
 * { message, errors: { campo: ["mensaje"] } }, sin el campo "success". Si en
 * algún momento el equipo cambia el formato de las respuestas, hay que
 * actualizar este archivo para que siga calzando.
 */

// ── Configuración base ──────────────────────────────────────────────────

// La URL del backend viene de la variable de entorno VITE_API_URL (definida
// en el archivo .env). Nunca hardcodeamos la URL acá directamente.
const API_BASE_URL = import.meta.env.VITE_API_URL as string;

if (!API_BASE_URL) {
  // Si esto aparece en la consola, significa que falta el archivo .env
  // o que no tiene la variable VITE_API_URL definida.
  console.error(
    'VITE_API_URL no está definida. Revisa que exista un archivo .env en la raíz del frontend con la línea VITE_API_URL=http://localhost:8000'
  );
}

const API_PREFIX = '/api/v1';

// ── Manejo del token de sesión ──────────────────────────────────────────

// Guardamos el token en localStorage para que la sesión sobreviva si se
// recarga la página. La clave tiene un nombre específico para no chocar con
// otras cosas que se guarden en localStorage.
const TOKEN_STORAGE_KEY = 'senavida_auth_token';

export function getStoredToken(): string | null {
  return localStorage.getItem(TOKEN_STORAGE_KEY);
}

export function setStoredToken(token: string): void {
  localStorage.setItem(TOKEN_STORAGE_KEY, token);
}

export function clearStoredToken(): void {
  localStorage.removeItem(TOKEN_STORAGE_KEY);
}

// ── Tipos de las respuestas del backend ─────────────────────────────────

// Así se ve una respuesta exitosa real (según AuthController.php):
// { success: true, data: {...} }
interface ApiSuccessResponse<T> {
  success: true;
  data: T;
}

// Así se ve un error "general" real (login fallido por cuenta desactivada,
// rate limiting, etc.):
// { success: false, error: { message: "..." } }
interface ApiErrorResponse {
  success: false;
  error: {
    message: string;
  };
}

// Cuando Laravel rechaza el request por validación (422), usa su formato
// nativo, que NO tiene "success" en absoluto:
// { message: "...", errors: { campo: ["mensaje"] } }
interface ApiValidationErrorResponse {
  message: string;
  errors: Record<string, string[]>;
}

// Esta es la clase de error que lanzamos cuando algo sale mal, para que
// los componentes puedan hacer try/catch y mostrar el mensaje adecuado.
export class ApiError extends Error {
  status: number;
  fieldErrors?: Record<string, string[]>;

  constructor(message: string, status: number, fieldErrors?: Record<string, string[]>) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.fieldErrors = fieldErrors;
  }
}

// ── El corazón del archivo: la función que hace las peticiones ─────────

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  body?: unknown;
  // Si el endpoint no requiere sesión iniciada (por ahora solo el login),
  // ponemos requiresAuth en false para no intentar mandar un token que no existe.
  requiresAuth?: boolean;
}

async function apiRequestRaw(path: string, options: RequestOptions = {}): Promise<any> {
  const { method = 'GET', body, requiresAuth = true } = options;

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  };

  if (requiresAuth) {
    const token = getStoredToken();
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }
  }

  let response: Response;
  try {
    response = await fetch(`${API_BASE_URL}${API_PREFIX}${path}`, {
      method,
      headers,
      body: body ? JSON.stringify(body) : undefined,
    });
  } catch (networkError) {
    // Esto se dispara si el backend no está corriendo, o si hay un problema
    // de CORS, o si no hay conexión a internet.
    throw new ApiError(
      'No se pudo conectar con el servidor. Verifica que el backend esté corriendo.',
      0
    );
  }

  // Caso especial: 204 No Content no trae cuerpo, así que no intentamos
  // parsear JSON (fallaría).
  if (response.status === 204) {
    return undefined;
  }

  const rawText = await response.text();

  // El servidor local está devolviendo un carácter '[' extra al inicio de
  // cada respuesta, antes del JSON real. Lo confirmamos con curl y con el
  // navegador, en distintos servidores (php artisan serve y Apache), así
  // que no depende del código del backend. No alcanzamos a encontrar la
  // causa exacta a tiempo, así que por ahora lo recortamos acá antes de
  // parsear. Nos queda pendiente investigar la causa real cuando tengamos
  // tiempo, y sacar este parche una vez que la resolvamos.
  let cleanText = rawText.trim();
  if (cleanText.startsWith('[') && !cleanText.endsWith(']')) {
    cleanText = cleanText.slice(1);
  }

  let json;
  try {
    json = JSON.parse(cleanText);
  } catch (parseError) {
    console.error('No se pudo parsear como JSON incluso después de limpiar:', rawText);
    throw parseError;
  }

  // Caso: error de validación (422). Laravel lo manda en su formato nativo,
  // distinto del resto de los errores.
  if (response.status === 422) {
    const validationError = json as ApiValidationErrorResponse;
    throw new ApiError(validationError.message, 422, validationError.errors);
  }

  // Caso: la respuesta trae success: false → es un error general
  // (credenciales incorrectas, cuenta desactivada, rate limiting, etc.)
  if ('success' in json && json.success === false) {
    const errorResponse = json as ApiErrorResponse;
    throw new ApiError(errorResponse.error.message, response.status);
  }

  // Caso: cualquier otro error HTTP que no tenga el formato esperado
  // (por ejemplo, un 500 con HTML en vez de JSON, o un error inesperado).
  if (!response.ok) {
    throw new ApiError(
      json.message || 'Ocurrió un error inesperado en el servidor.',
      response.status
    );
  }

  // Caso exitoso: llega completo, { success: true, data: {...}, meta?: {...} }
  return json;
}

async function apiRequest<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const json = await apiRequestRaw(path, options);
  if (json === undefined) return undefined as T;
  const successResponse = json as ApiSuccessResponse<T>;
  return successResponse.data;
}

/**
 * Igual que apiRequest, pero además devuelve el "meta" de la respuesta
 * (por ahora solo lo necesita listUsers(), para la paginación que trae
 * UserController::index()).
 */
async function apiRequestWithMeta<T>(
  path: string,
  options: RequestOptions = {}
): Promise<{ data: T; meta: any }> {
  const json = await apiRequestRaw(path, options);
  return { data: json.data, meta: json.meta };
}

// ── Tipos del dominio (según lo que devuelve AuthController.php) ───────

export type UserRole =
  | 'super_admin'
  | 'admin_institucional'
  | 'admision'
  | 'categorizacion'
  | 'medico'
  | 'paciente';

export interface AuthUser {
  id: string;
  name: string;
  email: string;
  role: UserRole;
  isActive: boolean;
}

export interface LoginResponse {
  token: string;
  tokenType: string;
  user: AuthUser;
}

export interface Organization {
  id: string;
  name: string;
  isActive: boolean;
  [key: string]: unknown;
}

export interface HealthCenter {
  id: string;
  name: string;
  organizationId: string;
  isActive: boolean;
  [key: string]: unknown;
}

export interface Unit {
  id: string;
  name: string;
  healthCenterId: string;
  isActive: boolean;
  [key: string]: unknown;
}

// ── Funciones de Autenticación (Módulo A — el que ya está 100% listo) ──

/**
 * Inicia sesión con email y contraseña. healthCenterId y unitId son
 * opcionales: el propio AuthController.php los valida solo si vienen
 * presentes ("sometimes" en su regla de validación), y si vienen, además
 * confirma que el usuario realmente pertenezca a ese centro y esa unidad
 * (si no, responde 403). Si es exitoso, guarda el token automáticamente
 * para que las siguientes peticiones ya vayan autenticadas.
 */
export async function login(
  email: string,
  password: string,
  healthCenterId?: string,
  unitId?: string
): Promise<AuthUser> {
  const data = await apiRequest<LoginResponse>('/auth/login', {
    method: 'POST',
    body: {
      email,
      password,
      ...(healthCenterId ? { healthCenterId } : {}),
      ...(unitId ? { unitId } : {}),
    },
    requiresAuth: false, // todavía no tenemos token en este punto
  });

  setStoredToken(data.token);
  return data.user;
}

/**
 * Trae los datos del usuario actualmente autenticado, según el token
 * guardado. La usamos justo después del login, y también al recargar la
 * página, para saber si la sesión sigue siendo válida.
 */
export async function fetchCurrentUser(): Promise<AuthUser> {
  const data = await apiRequest<{ user: AuthUser }>('/auth/me', {
    method: 'GET',
  });
  return data.user;
}

/**
 * Cierra la sesión en el servidor (revoca el token) y limpia el token
 * guardado localmente. Limpiamos el token local aunque la petición al
 * servidor falle, para no dejar a la persona con una sesión "fantasma"
 * en el navegador.
 */
export async function logout(): Promise<void> {
  try {
    await apiRequest<void>('/auth/logout', { method: 'POST' });
  } finally {
    clearStoredToken();
  }
}

// ── Funciones de Catálogos (Módulo listo: organizations, health-centers, units) ──
//
// El backend de Greudy y Camila ahora trae CRUD completo para las tres
// entidades: Crear, Leer, Actualizar, Desactivar (soft delete: no borra la
// fila, solo pone isActive=false) y Restaurar. Confirmamos el mismo patrón
// exacto en OrganizationController.php, HealthCenterController.php y
// UnitController.php antes de escribir esto.

/**
 * Por defecto el backend solo devuelve las activas ("status=active"). Con
 * status='all' traemos también las desactivadas, para poder mostrarlas
 * tachadas en la lista y ofrecer el botón de "Restaurar".
 */
export async function listOrganizations(status: 'active' | 'inactive' | 'all' = 'active'): Promise<Organization[]> {
  return apiRequest<Organization[]>(`/organizations?status=${status}`, { method: 'GET' });
}

export async function listHealthCenters(status: 'active' | 'inactive' | 'all' = 'active'): Promise<HealthCenter[]> {
  return apiRequest<HealthCenter[]>(`/health-centers?status=${status}`, { method: 'GET' });
}

/**
 * Trae las unidades de un centro de salud específico, o todas si no se
 * pasa healthCenterId. El backend soporta filtrar con este query param.
 */
export async function listUnits(
  healthCenterId?: string,
  status: 'active' | 'inactive' | 'all' = 'active'
): Promise<Unit[]> {
  const params = new URLSearchParams({ status });
  if (healthCenterId) params.set('healthCenterId', healthCenterId);
  return apiRequest<Unit[]>(`/units?${params.toString()}`, { method: 'GET' });
}

// ── Función de Registro de Usuarios (Módulo B) ──────────────────────────

export interface RegisterUserPayload {
  name: string;
  email: string;
  password: string;
  passwordConfirmation: string;
  role: UserRole;
  organizationId: string;
  healthCenterId: string;
  unitId: string;
}

export async function registerUser(payload: RegisterUserPayload): Promise<AuthUser> {
  // Ojo con este detalle: Laravel exige que el campo de confirmación de
  // contraseña se llame exactamente "password_confirmation" (con guion
  // bajo), porque es una convención fija de su regla `confirmed` — no seguir
  // el patrón camelCase del resto del contrato. Por eso lo traducimos acá,
  // para que el resto del código pueda seguir usando camelCase.
  const data = await apiRequest<{ user: AuthUser }>('/users', {
    method: 'POST',
    body: {
      name: payload.name,
      email: payload.email,
      password: payload.password,
      password_confirmation: payload.passwordConfirmation,
      role: payload.role,
      organizationId: payload.organizationId,
      healthCenterId: payload.healthCenterId,
      unitId: payload.unitId,
    },
  });
  return data.user;
}

// ── Funciones para crear catálogos (organizations, health-centers, units) ──
//
// Revisamos los controllers reales del backend para confirmar los permisos:
// - Crear una organización: solo super_admin.
// - Crear un centro de salud: solo super_admin.
// - Crear una unidad: super_admin o admin_institucional (pero admin_institucional
//   solo puede crearla dentro de su propio centro; eso lo valida el backend,
//   no hace falta que lo repitamos acá).

export async function createOrganization(name: string): Promise<Organization> {
  return apiRequest<Organization>('/organizations', {
    method: 'POST',
    body: { name },
  });
}

export async function createHealthCenter(name: string, organizationId: string): Promise<HealthCenter> {
  return apiRequest<HealthCenter>('/health-centers', {
    method: 'POST',
    body: { name, organizationId },
  });
}

export async function createUnit(name: string, healthCenterId: string): Promise<Unit> {
  return apiRequest<Unit>('/units', {
    method: 'POST',
    body: { name, healthCenterId },
  });
}

// ── Actualizar catálogos ─────────────────────────────────────────────────

export async function updateOrganization(id: string, name: string): Promise<Organization> {
  return apiRequest<Organization>(`/organizations/${id}`, {
    method: 'PUT',
    body: { name },
  });
}

export async function updateHealthCenter(id: string, name: string): Promise<HealthCenter> {
  return apiRequest<HealthCenter>(`/health-centers/${id}`, {
    method: 'PUT',
    body: { name },
  });
}

export async function updateUnit(id: string, name: string): Promise<Unit> {
  return apiRequest<Unit>(`/units/${id}`, {
    method: 'PUT',
    body: { name },
  });
}

// ── Desactivar catálogos (soft delete) ──────────────────────────────────
//
// Ninguno de los tres borra la fila de la base de datos: el backend solo
// marca isActive=false. Por eso el tipo de retorno trae isActive, no un
// simple "borrado con éxito".

export async function deactivateOrganization(id: string): Promise<{ id: string; isActive: boolean }> {
  return apiRequest(`/organizations/${id}`, { method: 'DELETE' });
}

export async function deactivateHealthCenter(id: string): Promise<{ id: string; isActive: boolean }> {
  return apiRequest(`/health-centers/${id}`, { method: 'DELETE' });
}

export async function deactivateUnit(id: string): Promise<{ id: string; isActive: boolean }> {
  return apiRequest(`/units/${id}`, { method: 'DELETE' });
}

// ── Restaurar catálogos ──────────────────────────────────────────────────

export async function restoreOrganization(id: string): Promise<{ id: string; isActive: boolean }> {
  return apiRequest(`/organizations/${id}/restore`, { method: 'PATCH' });
}

export async function restoreHealthCenter(id: string): Promise<{ id: string; isActive: boolean }> {
  return apiRequest(`/health-centers/${id}/restore`, { method: 'PATCH' });
}

export async function restoreUnit(id: string): Promise<{ id: string; isActive: boolean }> {
  return apiRequest(`/units/${id}/restore`, { method: 'PATCH' });
}

// ── Estadísticas del panel de administración ────────────────────────────
//
// Revisamos AdminStatsController.php: las 3 cifras se calculan en vivo en
// el servidor (usuarios activos del propio centro, peticiones a la API
// contadas en caché, y cobertura real de auditoría calculada por reflexión
// de PHP sobre qué modelos tienen el observador de auditoría conectado).
// Ninguna de las tres está escrita a mano ni en el backend ni acá.

export interface AdminStats {
  activeUsers: number;
  apiRequests: number;
  auditCoverage: number;
}

export async function fetchAdminStats(): Promise<AdminStats> {
  return apiRequest<AdminStats>('/admin/stats', { method: 'GET' });
}

// ── Configuración de seguridad ───────────────────────────────────────────
//
// Revisamos SecuritySettingController.php: el único campo real hoy es
// ctaMaxAttempts (cuántos intentos tiene un paciente para canjear su
// código de acceso temporal antes de bloquearse). Es exclusivo de
// admin_institucional — super_admin no tiene centro asignado, así que
// este endpoint no aplica para ese rol.

export interface SecuritySettings {
  id: string;
  healthCenterId: string;
  ctaMaxAttempts: number;
}

export async function fetchSecuritySettings(): Promise<SecuritySettings> {
  return apiRequest<SecuritySettings>('/security-settings', { method: 'GET' });
}

export async function updateSecuritySettings(ctaMaxAttempts: number): Promise<SecuritySettings> {
  return apiRequest<SecuritySettings>('/security-settings', {
    method: 'PUT',
    body: { ctaMaxAttempts },
  });
}

// ── CRUD completo de Usuarios ─────────────────────────────────────────────
//
// registerUser() (más arriba) ya cubre Crear. Estas funciones completan
// Leer, Actualizar y Desactivar/Restaurar, revisando el mismo patrón real
// de UserController.php:
// - Un admin_institucional solo ve/edita usuarios de su propio centro; el
//   backend lo filtra solo, nosotras no repetimos esa lógica acá.
// - Nadie puede desactivarse a sí mismo, ni cambiar su propio rol — si se
//   intenta, el backend lo rechaza y mostramos ese mensaje tal cual.

export interface UserListItem {
  id: string;
  name: string;
  email: string;
  role: UserRole;
  organizationId: string | null;
  healthCenterId: string | null;
  unitId: string | null;
  isActive: boolean;
}

export interface PaginatedUsers {
  users: UserListItem[];
  pagination: {
    total: number;
    count: number;
    perPage: number;
    currentPage: number;
    lastPage: number;
  };
}

/**
 * apiRequest() ya desenvuelve el "data" de la respuesta, pero acá también
 * necesitamos el "meta" (la paginación), que normalmente no usamos. Por
 * eso esta función hace la petición un poco distinto a las demás: llama a
 * apiRequestWithMeta en vez de apiRequest.
 */
export async function listUsers(page = 1): Promise<PaginatedUsers> {
  const result = await apiRequestWithMeta<UserListItem[]>(`/users?page=${page}`, { method: 'GET' });
  return {
    users: result.data,
    pagination: result.meta.pagination,
  };
}

export interface UpdateUserPayload {
  name?: string;
  email?: string;
  role?: UserRole;
}

export async function updateUser(id: string, payload: UpdateUserPayload): Promise<UserListItem> {
  return apiRequest<UserListItem>(`/users/${id}`, {
    method: 'PUT',
    body: payload,
  });
}

export async function deactivateUser(id: string): Promise<{ id: string; isActive: boolean }> {
  return apiRequest(`/users/${id}`, { method: 'DELETE' });
}

export async function restoreUser(id: string): Promise<{ id: string; isActive: boolean }> {
  return apiRequest(`/users/${id}/restore`, { method: 'PATCH' });
}