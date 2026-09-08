<?php

namespace App\Enums;

enum MessageType: string
{
    case Text = 'text';
    case QuickMessage = 'quick_message';
    case Pictogram = 'pictogram';
    case System = 'system';
}