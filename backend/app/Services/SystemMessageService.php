<?php

namespace App\Services;

use App\Enums\MessageOrigin;
use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Models\ChatMessage;
use App\Models\MedicalSession;

class SystemMessageService
{
    /**
     * Crea un mensaje de sistema dentro del chat de una atencion.
     * No lo emite ningun humano: senderType, senderId y senderName
     * quedan fijos, sin depender de quien disparo la accion.
     */
    public static function create(MedicalSession $session, string $body): ChatMessage
    {
        return ChatMessage::create([
            'medical_session_id'      => $session->id,
            'sender_type'             => 'system',
            'sender_id'               => null,
            'sender_name'             => 'Sistema',
            'message_type'            => MessageType::System->value,
            'body'                    => $body,
            'origin'                  => MessageOrigin::System->value,
            'status'                  => MessageStatus::Sent->value,
            'sent_at'                 => now(),
            'confirmed_by_patient_at' => null,
            'pictogram_id'            => null,
        ]);
    }
}