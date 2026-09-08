<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * ¿Puede listar organizaciones? (GET /organizations)
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin_institucional']);
    }

    /**
     * ¿Puede ver esta organización en particular? (GET /organizations/{id})
     */
    public function view(User $user, Organization $organization): bool
    {
        return in_array($user->role, ['super_admin', 'admin_institucional']);
    }

    /**
     * ¿Puede crear organizaciones? (POST /organizations)
     */
    public function create(User $user): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * ¿Puede editar esta organización? (PUT /organizations/{id})
     */
    public function update(User $user, Organization $organization): bool
    {
        return $user->role === 'super_admin';
    }

    /**
     * ¿Puede desactivar esta organización? (DELETE /organizations/{id})
     */
    public function delete(User $user, Organization $organization): bool
    {
        return $user->role === 'super_admin';
    }

    public function restore(User $user, Organization $organization): bool
   {
        return $user->role === 'super_admin';
    }

    public function forceDelete(User $user, Organization $organization): bool
    {
        return false;
    }
}