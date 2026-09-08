<?php

namespace App\Models;

use App\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

use App\Enums\ConsentStatus;
use App\Enums\ConsentType;
use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([AuditLogObserver::class])]
class Consent extends Model
{
    use HasUuids;

    protected $fillable = [
        'medical_session_id',
        'patient_id',
        'requested_by',
        'consent_type',
        'patient_contact_id',
        'status',
        'requested_at',
        'granted_at',
        'rejected_at',
        'revoked_at',
        'evidence',
    ];

    protected $casts = [
        'consent_type' => ConsentType::class,
        'status'       => ConsentStatus::class,
        'evidence'     => 'array',
        'requested_at' => 'datetime',
        'granted_at'   => 'datetime',
        'rejected_at'  => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    public function medicalSession(): BelongsTo
    {
        return $this->belongsTo(MedicalSession::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(PatientContact::class, 'patient_contact_id');
    }

    public function approve(array $evidence): void
    {
        $this->assertPuedeResponderse();

        $this->update([
            'status'     => ConsentStatus::Granted->value,
            'granted_at' => now(),
            'evidence'   => $evidence,
        ]);
    }

    public function reject(array $evidence): void
    {
        $this->assertPuedeResponderse();

        $this->update([
            'status'      => ConsentStatus::Rejected->value,
            'rejected_at' => now(),
            'evidence'    => $evidence,
        ]);
    }

    public function revoke(array $evidence): void
    {
        if (! $this->status->puedeRevocarse()) {
            throw new ApiException(
                'INVALID_CONSENT_TRANSITION',
                'Solo puedes revocar un consentimiento que hayas otorgado.',
                409,
            );
        }

        $this->update([
            'status'     => ConsentStatus::Revoked->value,
            'revoked_at' => now(),
            'evidence'   => $evidence,
        ]);
    }

    private function assertPuedeResponderse(): void
    {
        if (! $this->status->puedeResponderse()) {
            throw new ApiException(
                'CONSENT_ALREADY_ANSWERED',
                'Esta solicitud ya fue respondida.',
                409,
            );
        }
    }
}