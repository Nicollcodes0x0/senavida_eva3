<?php

namespace App\Policies;

use App\Models\ChatMessage;
use App\Models\MedicalSession;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ChatMessagePolicy
{
    /**
     * Regla compartida por view/create: staff de la misma unidad,
     * o el paciente dueno de la atencion.
     */
    public function view($user, MedicalSession $medicalSession): Response
    {
        if ($user instanceof Patient) {
            return $user->id === $medicalSession->patient_id
                ? Response::allow()
                : Response::deny('FORBIDDEN_PATIENT|Esta atencion no te pertenece.');
        }

        return $user->unit_id === $medicalSession->unit_id
            ? Response::allow()
            : Response::deny('FORBIDDEN_UNIT|No perteneces a la unidad de esta atencion.');
    }

    public function create($user, MedicalSession $medicalSession): Response
    {
        return $this->view($user, $medicalSession);
    }

    /**
     * Confirmar un mensaje: exclusivo del paciente dueno.
     */
    public function confirm(Patient $patient, ChatMessage $message): Response
    {
        return $patient->id === $message->medicalSession->patient_id
            ? Response::allow()
            : Response::deny('FORBIDDEN_PATIENT|No puedes confirmar mensajes de otra atencion.');
    }

    /**
     * Marcar como leido: exclusivo del staff de la unidad.
     */
    public function markAsRead(User $user, ChatMessage $message): Response
    {
        return $user->unit_id === $message->medicalSession->unit_id
            ? Response::allow()
            : Response::deny('FORBIDDEN_UNIT|No perteneces a la unidad de esta atencion.');
    }
}