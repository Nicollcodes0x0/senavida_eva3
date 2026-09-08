<?php

namespace App\Models;

use App\Observers\AuditLogObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

#[ObservedBy([AuditLogObserver::class])]
class TemporaryAccessCode extends Model
{
    use HasUuids;

    protected $fillable = [
        'patient_id',
        'health_center_id',
        'code_hash',
        'status',
        'expires_at',
        'used_at',
        'failed_attempts',
        'max_attempts',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    /**
     * Un CTA pertenece a un paciente.
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Un CTA pertenece a un centro de salud.
     */
    public function healthCenter(): BelongsTo
    {
        return $this->belongsTo(HealthCenter::class);
    }

    /**
     * Genera un código nuevo en formato SV-XXXXXX (6 dígitos aleatorios).
     * Devuelve el código EN CLARO — quien llame a este método es
     * responsable de guardarlo solo como hash y no conservarlo.
     */
    public static function generateCode(): string
    {
        $digits = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        return 'SV-'.$digits;
    }

    /**
     * Compara un código en claro contra el hash guardado en este registro.
     */
    public function matchesCode(string $plainCode): bool
    {
        return Hash::check(strtoupper($plainCode), $this->code_hash);
    }

    /**
     * ¿Este código ya venció?
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * ¿Este código ya agotó sus intentos?
     */
    public function isBlocked(): bool
    {
        return $this->status === 'blocked' || $this->failed_attempts >= $this->max_attempts;
    }
}