<?php

namespace App\Policies;

use App\Models\HealthCenter;
use App\Models\User;

class HealthCenterPolicy
{
    /**
     * ¿Puede listar centros de salud? (GET /health-centers)
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin_institucional']);
    }

    /**
     * ¿Puede ver este centro en particular? (GET /health-centers/{id})
     */
    public function view(User $user, HealthCenter $healthCenter): bool
    {
        return in_array($user->role, ['super_admin', 'admin_institucional']);
    }

    /**
     * ¿Puede crear centros de salud? (POST /health-centers)
     */
    public function create(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * ¿Puede editar este centro? (PUT /health-centers/{id})
     */
    public function update(User $user, HealthCenter $healthCenter): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * ¿Puede desactivar este centro? (DELETE /health-centers/{id})
     */
    public function delete(User $user, HealthCenter $healthCenter): bool
    {
        return $user->role === 'super_admin';
    }

    public function restore(User $user, HealthCenter $healthCenter): bool
    {
        return $user->role === 'super_admin';
    }

    public function forceDelete(User $user, HealthCenter $healthCenter): bool
    {
        return false;
    }
}