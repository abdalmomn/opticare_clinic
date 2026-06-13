<?php

namespace App\Modules\Chat\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum ConversationStatusEnum: string
{
    use HasEnumValues;

    case OPEN = 'open';
    case CLOSED = 'closed';
    case WAITING = 'waiting';
}
