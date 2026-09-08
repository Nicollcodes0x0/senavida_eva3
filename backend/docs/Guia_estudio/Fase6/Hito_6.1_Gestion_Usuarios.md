# Guía de Estudio — Hito 6.1: Gestión de usuarios

## 1. Objetivo del hito

Corregir una vulnerabilidad crítica de escalación de privilegios en el CRUD de usuarios, y completar el listado (`GET /users`) con paginación, filtros y ordenamiento según el contrato.

## 2. ¿Por qué era necesario?

El CRUD de usuarios ya estaba casi completo desde fases anteriores, pero una revisión de seguridad detectó que la validación del campo `role` no distinguía **quién** estaba pidiendo crear o editar un usuario. Cualquier `admin_institucional` podía otorgarse a sí mismo, o a cualquier otro, el rol `super_admin` — el de mayor privilegio del sistema completo.

## 3. Conceptos clave aprendidos

### 3.1 Policy vs. FormRequest — dos preguntas distintas

Es el concepto más importante de este hito.

| Pregunta | Quién la responde | Ejemplo |
|---|---|---|
| "¿Puede esta persona, en general, hacer esta acción?" | **Policy** | ¿Puede crear usuarios? Sí, si es `admin_institucional` o `super_admin`. |
| "¿Es válido el **valor** que mandó en este campo?" | **FormRequest** | ¿El `role` que pidió es uno que él puede otorgar? |

El bug ocurría porque la Policy respondía correctamente su pregunta ("sí, puede crear usuarios"), pero nadie más adelante preguntaba "¿puede otorgar *ese* rol en particular?". Esa segunda pregunta es sobre el **dato**, no sobre el permiso general — por eso vive en el `FormRequest`, no en la Policy.

**Analogía:** la Policy es el guardia que te deja entrar al banco porque tienes cuenta ahí. El FormRequest es el cajero que revisa si el cheque que traes es válido para la cantidad que pides retirar. Ambos son controles de seguridad, pero verifican cosas distintas.

### 3.2 Roles estructurales vs. roles operativos

Para decidir qué rol puede otorgar cada quien, se estableció una jerarquía de dos niveles:

- **Estructurales** (`super_admin`, `admin_institucional`): gestionan la plataforma misma — organizaciones, centros, personal.
- **Operativos** (`admision`, `categorizacion`, `medico`): hacen el trabajo clínico del día a día.

**Regla aplicada:** un rol operativo nunca debe poder fabricar un rol estructural. Un `admin_institucional` (estructural, pero acotado a un centro) solo puede otorgar roles operativos. Solo `super_admin` puede otorgar cualquier rol, incluidos los estructurales.

```php
private function allowedRoles(): array
{
    $actor = $this->user();

    if ($actor->role === 'super_admin') {
        return ['super_admin', 'admin_institucional', 'admision', 'categorizacion', 'medico'];
    }

    return ['admision', 'categorizacion', 'medico'];
}
```

Esta lista se calcula dinámicamente **antes** de validar, y se usa en la regla `Rule::in($this->allowedRoles())`. Si el valor pedido no está en la lista, Laravel devuelve `422` automáticamente.

### 3.3 `withValidator()` — reglas que comparan varios campos

Las reglas normales de un `FormRequest` (`rules()`) validan **un campo a la vez**, de forma aislada. Pero "¿te estás editando a ti mismo?" necesita comparar dos datos que no viven en el mismo lugar: el `id` de la URL (a quién se está editando) contra el `id` de quien hace la petición.

```php
public function withValidator(Validator $validator): void
{
    $validator->after(function (Validator $validator) {
        $targetUser = $this->route('user');
        $isEditingSelf = $targetUser && $targetUser->id === $this->user()->id;

        if ($isEditingSelf && $this->filled('role')) {
            $validator->errors()->add('role', 'No puedes cambiar tu propio rol.');
        }
    });
}
```

`withValidator()` es el gancho de Laravel para este tipo de reglas "transversales" — se ejecuta después de las reglas normales, con acceso a la petición completa.

### 3.4 Paginación con `paginate()`

Sin paginación, `GET /users` devolvería todos los usuarios del sistema de golpe — insostenible cuando hay miles de registros. Laravel resuelve esto con un solo método:

```php
$paginated = $query->paginate($perPage);
```

Esto devuelve un objeto que ya trae calculado: cuántos registros hay en total, en qué página estás, cuántas páginas hay en total. No hay que contar nada a mano.

## 4. Endpoints afectados

| Endpoint | Verbo | Qué cambió |
|---|---|---|
| `/users` | `POST` | Ahora valida qué rol puede otorgar quien pide la creación |
| `/users/{id}` | `PUT` | Misma validación de rol, más el bloqueo de auto-modificación |
| `/users` | `GET` | Agrega paginación, filtros (`role`, `healthCenterId`, `unitId`, `isActive`) y `sort` |

## 5. Cómo se verificó

Seis pruebas con evidencia HTTP real, incluyendo el caso que motivó todo el hito:

1. Un `admin_institucional` intentando crear un `super_admin` → `422` bloqueado.
2. El mismo admin creando un `medico` (rol permitido) → `201` exitoso — confirma que el bloqueo es específico, no general.
3. El admin intentando cambiar su propio rol → `422` con **dos** mensajes de error combinados.
4. El admin editando el rol de **otra** persona → `200` exitoso — confirma que la protección de auto-modificación no afecta la gestión legítima de terceros.
5. y 6. Paginación por defecto y filtro por `role`, ambos verificados con datos reales.

## 6. Resumen — qué aprendí

1. Autorización general (Policy) y validación de datos (FormRequest) son capas distintas — un bug de seguridad puede colarse si solo se protege una de las dos.
2. Las restricciones dependientes del actor (quién pide la acción) se calculan dinámicamente dentro del `FormRequest`, no se codifican como una lista fija.
3. `withValidator()` permite reglas que comparan varios campos o datos de contexto (como el usuario autenticado) que las reglas simples no pueden expresar.
4. La paginación no es "cortar la lista" manualmente — Laravel ya trae la herramienta (`paginate()`) que calcula todo el metadato necesario.
5. Un bug de seguridad grave puede convivir perfectamente con código que "funciona" — pasa las pruebas básicas, pero abre una puerta que nadie pidió abrir. Solo se encuentra revisando la lógica de negocio a fondo, no solo probando el camino feliz.
