<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                    => $this->id,
            'sessionId'             => $this->medical_session_id,
            'senderType'            => $this->sender_type,
            'senderId'              => $this->sender_id,
            'senderName'            => $this->sender_name,
            'messageType'           => $this->message_type->value,
            'body'                  => $this->body,
            'origin'                => $this->origin->value,
            'status'                => $this->status->value,
            'sentAt'                => $this->sent_at,
            'confirmedByPatientAt'  => $this->confirmed_by_patient_at,
            'pictogramId'           => $this->pictogram_id,
            'pictogram'             => $this->whenLoaded('pictogram', fn () => [
                'id'    => $this->pictogram->id,
                'title' => $this->pictogram->title,
                'emoji' => $this->pictogram->emoji,
            ]),
        ];
    }
}