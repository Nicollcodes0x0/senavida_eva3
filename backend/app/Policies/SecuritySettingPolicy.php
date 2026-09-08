<?php

namespace App\Policies;

use App\Models\SecuritySetting;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SecuritySettingPolicy
{
    /**
     * Solo admin_institucional ve la configuracion de seguridad, y solo
     * la de su propio centro. super_admin queda fuera: la configuracion
     * de seguridad es operacion de un centro especifico, no estructura
     * del sistema (mismo criterio que con pictogramas, Hito 5.1).
     */
    public function view(User $user, SecuritySetting $setting): Response
    {
        if ($user->role !== 'admin_institucional') {
            return Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede ver la configuracion de seguridad.');
        }

        return $setting->health_center_id === $user->health_center_id
            ? Response::allow()
            : Response::deny('FORBIDDEN_CENTER|Solo puedes ver la configuracion de tu propio centro.');
    }

    public function update(User $user, SecuritySetting $setting): Response
    {
        if ($user->role !== 'admin_institucional') {
            return Response::deny('FORBIDDEN_ROLE|Solo un administrador institucional puede editar la configuracion de seguridad.');
        }

        return $setting->health_center_id === $user->health_center_id
            ? Response::allow()
            : Response::deny('FORBIDDEN_CENTER|Solo puedes editar la configuracion de tu propio centro.');
    }
}