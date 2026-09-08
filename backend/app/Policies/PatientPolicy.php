<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    /**
     * ¿Puede listar pacientes? (GET /patients)
     * Solo personal clínico que necesita consultar fichas.
     * super_admin y admin_institucional quedan fuera A PROPÓSITO:
     * la administración de la plataforma no incluye acceso a datos
     * clínicos (mismo principio aplicado en MedicalSessionPolicy).
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admision', 'categorizacion', 'medico']);
    }

    /**
     * ¿Puede ver este paciente en particular? (GET /patients/{id})
     */
    public function view(User $user, Patient $patient): bool
    {
        return in_array($user->role, ['admision', 'categorizacion', 'medico']);
    }

    /**
     * ¿Puede crear pacientes? (POST /patients)
     * NADIE del personal clínico puede hacerlo — el registro de un
     * paciente es un endpoint PÚBLICO, fuera de este sistema de permisos.
     * Este método existe por convención de Laravel, pero nunca se invoca
     * desde el controlador de autorregistro.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * ¿Puede editar este paciente? (no existe endpoint para esto)
     * Regla del contrato: "NINGÚN rol clínico puede modificar esta entidad."
     */
    public function update(User $user, Patient $patient): bool
    {
        return false;
    }

    /**
     * ¿Puede desactivar este paciente? (no existe endpoint para esto)
     * Misma regla: ni siquiera el super_admin puede tocar la ficha del paciente.
     */
    public function delete(User $user, Patient $patient): bool
    {
        return false;
    }

    public function restore(User $user, Patient $patient): bool
    {
        return false;
    }

    public function forceDelete(User $user, Patient $patient): bool
    {
        return false;
    }
}