<?php

namespace App\Enums;

enum PictogramSeverity: string
{
    case Critical = 'critical';
    case Warning = 'warning';
    case Info = 'info';
    case Neutral = 'neutral';
}