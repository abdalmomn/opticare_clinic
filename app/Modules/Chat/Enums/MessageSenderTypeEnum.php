<?php

namespace App\Modules\Chat\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum MessageSenderTypeEnum: string
{
    use HasEnumValues;

    case PATIENT = 'patient';
    case STAFF = 'staff';
    case AI = 'ai';
}
