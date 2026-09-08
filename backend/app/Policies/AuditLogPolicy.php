<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class AuditLogPolicy
{
    /**
     * Solo admin_institucional consulta la bitacora, y solo la de su
     * propio centro. Ni siquiera super_admin accede - decision explicita
     * del contrato (SS13.7): "Solo admin_institucional accede".
     */
    public function viewAny(User $user): Response
    {
        return $user->role === 'admin_institucional'
            ? Response::allow()
            : Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede consultar la bitacora de auditoria.');
    }
}