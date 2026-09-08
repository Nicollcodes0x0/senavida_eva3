<?php

namespace App\Models;

use App\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([AuditLogObserver::class])]
class Patient extends Model
{
    use HasApiTokens, HasUuids;

    protected $fillable = [
        'name',
        'national_id',
        'national_id_type',
        'birth_date',
        'health_insurance',
        'address',
        'phone',
        'primary_health_center',
        'allergies',
        'health_conditions',
        'communication_preference',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'is_active'  => 'boolean',
    ];

    /**
     * Un paciente tiene muchos contactos de emergencia.
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(PatientContact::class);
    }

    /**
     * Edad calculada a partir de la fecha de nacimiento.
     * No se almacena en la base de datos (regla del contrato: A-XX).
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->birth_date->age,
        );
    }
}