<?php

namespace App\Enums;

enum MedicalSessionStatus: string
{
    case InAdmission = 'in_admission';
    case InTriage = 'in_triage';
    case InMedicalCare = 'in_medical_care';
    case Closed = 'closed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::InAdmission => 'Admisión',
            self::InTriage => 'Categorización',
            self::InMedicalCare => 'Consulta Médica',
            self::Closed => 'Cerrado',
            self::Cancelled => 'Cancelada',
            self::Expired => 'Expirada',
        };
    }

    public function isOpen(): bool
    {
        return ! in_array($this, [self::Closed, self::Cancelled, self::Expired]);
    }

    /**
     * El único siguiente estado válido, o null si esta etapa
     * no tiene un "siguiente" (le corresponde a S5 cerrarla,
     * no a S4 avanzarla).
     */
    public function next(): ?self
    {
        return match ($this) {
            self::InAdmission => self::InTriage,
            self::InTriage => self::InMedicalCare,
            default => null,
        };
    }
}