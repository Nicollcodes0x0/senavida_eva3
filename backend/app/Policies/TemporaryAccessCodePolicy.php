<?php

namespace App\Policies;

use App\Models\TemporaryAccessCode;
use App\Models\User;

class TemporaryAccessCodePolicy
{
    /**
     * ¿Puede validar códigos CTA? (POST /attention-codes/validate)
     * La generación (POST /patients/{id}/attention-codes) es PUBLICA,
     * la hace el paciente, y por lo tanto no pasa por esta Policy.
     */
    public function validateCode(User $user): bool
    {
        return in_array($user->role, ['admision', 'super_admin']);
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, TemporaryAccessCode $temporaryAccessCode): bool
    {
        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, TemporaryAccessCode $temporaryAccessCode): bool
    {
        return false;
    }

    public function delete(User $user, TemporaryAccessCode $temporaryAccessCode): bool
    {
        return false;
    }

    public function restore(User $user, TemporaryAccessCode $temporaryAccessCode): bool
    {
        return false;
    }

    public function forceDelete(User $user, TemporaryAccessCode $temporaryAccessCode): bool
    {
        return false;
    }
}