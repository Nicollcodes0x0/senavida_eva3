<?php

namespace App\Policies;

use App\Models\Consent;
use App\Models\MedicalSession;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ConsentPolicy
{
    /**
     * Ver los consentimientos de una atencion: staff de la unidad
     * o el paciente dueno.
     */
    public function viewAny($user, MedicalSession $medicalSession): Response
    {
        if ($user instanceof Patient) {
            return $user->id === $medicalSession->patient_id
                ? Response::allow()
                : Response::deny('FORBIDDEN_PATIENT|Esta atención no te pertenece.');
        }

        return $user->unit_id === $medicalSession->unit_id
            ? Response::allow()
            : Response::deny('FORBIDDEN_UNIT|No perteneces a la unidad de esta atención.');
    }

    /**
     * Solicitar un consentimiento: solo el medico de la unidad.
     */
    public function create(User $user, MedicalSession $medicalSession): Response
    {
        if ($user->role !== 'medico') {
            return Response::deny('FORBIDDEN_ROLE|Solo un médico puede solicitar consentimientos.');
        }

        return $user->unit_id === $medicalSession->unit_id
            ? Response::allow()
            : Response::deny('FORBIDDEN_UNIT|No perteneces a la unidad de esta atención.');
    }

    /**
     * Aprobar, rechazar o revocar: exclusivo del paciente dueno.
     * El type-hint estricto Patient impide que un User de staff
     * llegue siquiera a evaluarse aqui.
     */
    public function respond(Patient $patient, Consent $consent): Response
    {
        return $patient->id === $consent->patient_id
            ? Response::allow()
            : Response::deny('FORBIDDEN_PATIENT|Este consentimiento no te pertenece.');
    }
}