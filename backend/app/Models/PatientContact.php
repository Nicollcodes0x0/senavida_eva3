<?php

namespace App\Models;

use App\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([AuditLogObserver::class])]
class PatientContact extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'name',
        'relationship',
        'phone',
    ];

    /**
     * Un contacto de emergencia pertenece a un paciente.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}