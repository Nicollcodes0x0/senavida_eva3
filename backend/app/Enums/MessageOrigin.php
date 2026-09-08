<?php

namespace App\Enums;

enum MessageOrigin: string
{
    case Patient = 'patient';
    case Admission = 'admission';
    case Triage = 'triage';
    case Doctor = 'doctor';
    case System = 'system';
}