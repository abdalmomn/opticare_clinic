<?php

namespace App\Modules\Imaging\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum ImagingQueueStatusEnum: string
{
    use HasEnumValues;

    case WAITING = 'waiting';
    case DISPATCHED = 'dispatched';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
