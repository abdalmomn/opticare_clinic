<?php

namespace App\Modules\Chat\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum MessageTypeEnum: string
{
    use HasEnumValues;

    case TEXT = 'text';
    case IMAGE = 'image';
    case FILE = 'file';
    case VOICE = 'voice';
}
