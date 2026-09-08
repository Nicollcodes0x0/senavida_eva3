<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $contactName = $this->contact?->name;

        return [
            'id'          => $this->id,
            'sessionId'   => $this->medical_session_id,
            'consentType' => $this->consent_type->value,
            'title'       => $this->consent_type->title(),
            'description' => $this->consent_type->description($contactName),
            'status'      => $this->status->value,
            'statusLabel' => $this->status->label(),
            'contactId'   => $this->patient_contact_id,
            'contactName' => $contactName,
            'requestedBy' => [
                'id'   => $this->requester->id,
                'name' => $this->requester->name,
            ],
            'requestedAt' => $this->requested_at,
            'grantedAt'   => $this->granted_at,
            'rejectedAt'  => $this->rejected_at,
            'revokedAt'   => $this->revoked_at,
        ];
    }
}