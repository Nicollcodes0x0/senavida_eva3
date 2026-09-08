<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * ¿Puede listar usuarios? (GET /users)
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin_institucional']);
    }

    /**
     * ¿Puede ver este usuario en particular? (GET /users/{id})
     */
    public function view(User $user, User $model): bool
    {
        if (! in_array($user->role, ['super_admin', 'admin_institucional'])) {
            return false;
        }

        if ($user->role === 'admin_institucional') {
            return $model->health_center_id === $user->health_center_id;
        }

        return true; // super_admin ve a cualquiera
    }

    /**
     * ¿Puede crear usuarios? (POST /users)
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin_institucional']);
    }

    /**
     * ¿Puede editar este usuario? (PUT /users/{id})
     */
    public function update(User $user, User $model): bool
    {
        if (! in_array($user->role, ['super_admin', 'admin_institucional'])) {
            return false;
        }

        if ($user->role === 'admin_institucional') {
            return $model->health_center_id === $user->health_center_id;
        }

        return true;
    }

    /**
     * ¿Puede desactivar este usuario? (DELETE /users/{id})
     */
    public function delete(User $user, User $model): bool
    {
        // Nadie puede desactivarse a sí mismo
        if ($user->id === $model->id) {
            return false;
        }

        if (! in_array($user->role, ['super_admin', 'admin_institucional'])) {
            return false;
        }

        if ($user->role === 'admin_institucional') {
            return $model->health_center_id === $user->health_center_id;
        }

        return true;
    }

    public function restore(User $user, User $model): bool
{
    if (! in_array($user->role, ['super_admin', 'admin_institucional'])) {
        return false;
    }

    if ($user->role === 'admin_institucional') {
        return $model->health_center_id === $user->health_center_id;
    }

    return true;
}

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}