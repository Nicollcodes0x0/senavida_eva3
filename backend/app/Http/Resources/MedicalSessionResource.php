<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MedicalSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ctaCode' => $this->cta_code,
            'status' => $this->status->value,
            'statusLabel' => $this->status->label(),
            'isWritable' => $this->status->isOpen(),
            'triageSkipped' => $this->triage_skipped,
            'triageSkipReason' => $this->triage_skip_reason,
            'triageSkippedBy' => $this->whenLoaded('triageSkippedBy', fn () => [
                'id' => $this->triageSkippedBy->id,
                'name' => $this->triageSkippedBy->name,
            ]),
            'reasonOfVisit' => $this->reason_of_visit,
            'startedAt' => $this->started_at,
            'endedAt' => $this->ended_at,
            'closureReason' => $this->closure_reason,
            'summary' => $this->summary,

            'patient' => [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
                'age' => $this->patient->age,
                'communicationPreference' => $this->patient->communication_preference,
                'allergies' => $this->allergies ?? [],
            ],

            'healthCenterId' => $this->health_center_id,
            'healthCenterName' => $this->healthCenter->name,
            'unitId' => $this->unit_id,
            'unitName' => $this->unit->name,

            'createdBy' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ],
            'closedBy' => $this->closer ? [
                'id' => $this->closer->id,
                'name' => $this->closer->name,
            ] : null,
        ];
    }
}