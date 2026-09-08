<?php

namespace App\Enums;

enum ConsentStatus: string
{
    case Pending = 'pending';
    case Granted = 'granted';
    case Rejected = 'rejected';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Granted => 'Otorgado',
            self::Rejected => 'Rechazado',
            self::Revoked => 'Revocado',
        };
    }

    /**
     * Solo una solicitud pendiente admite respuesta del paciente.
     */
    public function puedeResponderse(): bool
    {
        return $this === self::Pending;
    }

    /**
     * Solo se revoca lo que antes se otorgo: no tiene sentido
     * revocar un permiso que nunca se dio.
     */
    public function puedeRevocarse(): bool
    {
        return $this === self::Granted;
    }
}