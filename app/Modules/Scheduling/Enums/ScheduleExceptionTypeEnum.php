<?php

namespace App\Modules\Scheduling\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum ScheduleExceptionTypeEnum: string
{
    use HasEnumValues;

    case HOLIDAY = 'holiday';
    case MAINTENANCE = 'maintenance';
    case EMERGENCY = 'emergency';
    case CUSTOM = 'custom';
}
