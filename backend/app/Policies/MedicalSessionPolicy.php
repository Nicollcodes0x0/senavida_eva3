<?php

namespace App\Policies;

use App\Enums\MedicalSessionStatus;
use App\Models\MedicalSession;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MedicalSessionPolicy
{
    private const CLINICAL_ROLES = ['admision', 'categorizacion', 'medico'];

    private const STAGE_OWNERS = [
        'in_admission' => 'admision',
        'in_triage' => 'categorizacion',
    ];

    private function mismaUnidad(User $user, MedicalSession $session): bool
    {
        return $user->health_center_id === $session->health_center_id
            && $user->unit_id === $session->unit_id;
    }

    public function viewAny(User $user): bool
    {
        return in_array($user->role, self::CLINICAL_ROLES);
    }

    public function view(User $user, MedicalSession $session): Response
    {
        if (! in_array($user->role, self::CLINICAL_ROLES)) {
            return Response::deny('FORBIDDEN_ROLE|Tu rol no tiene acceso a las atenciones medicas.');
        }

        if (! $this->mismaUnidad($user, $session)) {
            return Response::deny('FORBIDDEN_CENTER|Esta atencion pertenece a otra unidad.');
        }

        return Response::allow();
    }

    public function create(User $user): Response
    {
        return $user->role === 'admision'
            ? Response::allow()
            : Response::deny('FORBIDDEN_ROLE|Solo el personal de Admision puede abrir una atencion.');
    }

    public function advance(User $user, MedicalSession $session): Response
    {
        if (! $this->mismaUnidad($user, $session)) {
            return Response::deny('FORBIDDEN_CENTER|Esta atencion pertenece a otra unidad.');
        }

        $duenoDelTramo = self::STAGE_OWNERS[$session->status->value] ?? null;

        if ($duenoDelTramo === null) {
            return Response::deny('INVALID_STAGE_TRANSITION|Esta atencion no tiene una siguiente etapa disponible.');
        }

        if ($user->role !== $duenoDelTramo) {
            return Response::deny("FORBIDDEN_ROLE|Esta etapa corresponde avanzarla a {$duenoDelTramo}, no a tu rol.");
        }

        return Response::allow();
    }

    /**
     * D-23 resuelto: permite saltar Categorizacion en una emergencia.
     * Solo medico, solo si la atencion sigue abierta y aun no llego
     * a consulta medica. El motivo y el autor quedan auditados.
     */
    public function emergencyAdvance(User $user, MedicalSession $session): Response
    {
        if ($user->role !== 'medico') {
            return Response::deny('FORBIDDEN_ROLE|Solo el rol medico puede tomar un caso de emergencia.');
        }

        if (! $this->mismaUnidad($user, $session)) {
            return Response::deny('FORBIDDEN_CENTER|Esta atencion pertenece a otra unidad.');
        }

        if (! in_array($session->status, [MedicalSessionStatus::InAdmission, MedicalSessionStatus::InTriage], true)) {
            return Response::deny('INVALID_STAGE_TRANSITION|Esta atencion ya esta en consulta medica o cerrada.');
        }

        return Response::allow();
    }

    public function close(User $user, MedicalSession $session): Response
    {
        if ($user->role !== 'medico') {
            return Response::deny('FORBIDDEN_ROLE|Solo el rol medico puede cerrar una atencion.');
        }

        if (! $this->mismaUnidad($user, $session)) {
            return Response::deny('FORBIDDEN_CENTER|Esta atencion pertenece a otra unidad.');
        }

        if ($session->status !== MedicalSessionStatus::InMedicalCare) {
            return Response::deny('INVALID_STAGE_TRANSITION|Esta atencion aun no ha llegado a Consulta Medica.');
        }

        return Response::allow();
    }

    public function update(User $user, MedicalSession $session): bool
    {
        return false;
    }

    public function delete(User $user, MedicalSession $session): bool
    {
        return false;
    }

    public function restore(User $user, MedicalSession $session): bool
    {
        return false;
    }

    public function forceDelete(User $user, MedicalSession $session): bool
    {
        return false;
    }
}