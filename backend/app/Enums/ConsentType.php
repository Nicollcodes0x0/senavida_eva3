<?php

namespace App\Enums;

enum ConsentType: string
{
    case StartCare = 'start_care';
    case ClinicalData = 'clinical_data';
    case Camera = 'camera';
    case ShareWithContacts = 'share_with_contacts';

    case Interpreter = 'interpreter';
    case Recording = 'recording';
    case DataRetention = 'data_retention';
    case Research = 'research';
    case Teaching = 'teaching';
    case Transfer = 'transfer';
    case Telemedicine = 'telemedicine';

    /**
     * El personal NO escribe este texto: se genera aqui para evitar
     * que un error de tipeo autorice algo distinto a lo que el
     * paciente cree estar aprobando (D-08).
     */
    public function title(): string
    {
        return match ($this) {
            self::StartCare => 'Inicio de la atención',
            self::ClinicalData => 'Uso de datos clínicos',
            self::Camera => 'Uso de cámara',
            self::ShareWithContacts => 'Compartir con contacto de emergencia',
            self::Interpreter => 'Intérprete de lengua de señas',
            self::Recording => 'Grabación de la atención',
            self::DataRetention => 'Conservación de datos',
            self::Research => 'Uso con fines de investigación',
            self::Teaching => 'Uso con fines docentes',
            self::Transfer => 'Traslado a otro centro',
            self::Telemedicine => 'Atención por telemedicina',
        };
    }

    public function description(?string $contactName = null): string
    {
        return match ($this) {
            self::StartCare => 'Autorizas al equipo de salud a iniciar tu atención médica.',
            self::ClinicalData => 'Autorizas el registro y uso de tus datos clínicos durante esta atención.',
            self::Camera => 'Autorizas el uso de la cámara para comunicarte en lengua de señas.',
            self::ShareWithContacts => $contactName === null
                ? 'Autorizas compartir información de tu atención con tu contacto de emergencia.'
                : "Autorizas compartir información de tu atención con {$contactName}.",
            self::Interpreter => 'Autorizas la participación de un intérprete de lengua de señas.',
            self::Recording => 'Autorizas la grabación de esta atención.',
            self::DataRetention => 'Autorizas la conservación de tus datos según la normativa vigente.',
            self::Research => 'Autorizas el uso anonimizado de tus datos con fines de investigación.',
            self::Teaching => 'Autorizas el uso anonimizado de tu caso con fines docentes.',
            self::Transfer => 'Autorizas tu traslado a otro centro de salud.',
            self::Telemedicine => 'Autorizas ser atendido por videollamada.',
        };
    }

    public static function implementados(): array
    {
        return [
            self::StartCare->value,
            self::ClinicalData->value,
            self::Camera->value,
            self::ShareWithContacts->value,
        ];
    }

    public function requiereContacto(): bool
    {
        return $this === self::ShareWithContacts;
    }
}