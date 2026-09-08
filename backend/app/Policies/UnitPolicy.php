<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    /**
     * ¿Puede listar unidades? (GET /units)
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin_institucional']);
    }

    /**
     * ¿Puede ver esta unidad en particular? (GET /units/{id})
     */
    public function view(User $user, Unit $unit): bool
    {
        if (! in_array($user->role, ['super_admin', 'admin_institucional'])) {
            return false;
        }

        if ($user->role === 'admin_institucional') {
            return $unit->health_center_id === $user->health_center_id;
        }

        return true; // super_admin ve cualquier unidad
    }

    /**
     * ¿Puede crear unidades? (POST /units)
     * Nota: solo valida el ROL aquí. La comprobación de "en qué centro
     * específico" se queda en el controlador, porque al crear no existe
     * todavía una $unit real con la cual comparar.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin_institucional']);
    }

    /**
     * ¿Puede editar esta unidad? (PUT /units/{id})
     */
    public function update(User $user, Unit $unit): bool
    {
        if (! in_array($user->role, ['super_admin', 'admin_institucional'])) {
            return false;
        }

        if ($user->role === 'admin_institucional') {
            return $unit->health_center_id === $user->health_center_id;
        }

        return true;
    }

    /**
     * ¿Puede desactivar esta unidad? (DELETE /units/{id})
     */
    public function delete(User $user, Unit $unit): bool
    {
        if (! in_array($user->role, ['super_admin', 'admin_institucional'])) {
            return false;
        }

        if ($user->role === 'admin_institucional') {
            return $unit->health_center_id === $user->health_center_id;
        }

        return true;
    }

    public function restore(User $user, Unit $unit): bool
    {
        if (! in_array($user->role, ['super_admin', 'admin_institucional'])) {
            return false;
        }

        if ($user->role === 'admin_institucional') {
            return $unit->health_center_id === $user->health_center_id;
        }

        return true;
    }

    public function forceDelete(User $user, Unit $unit): bool
    {
        return false;
    }
}