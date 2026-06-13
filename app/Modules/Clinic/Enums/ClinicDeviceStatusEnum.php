<?php

namespace App\Modules\Clinic\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum ClinicDeviceStatusEnum: string
{
    use HasEnumValues;

    case ACTIVE = 'active';
    case MAINTENANCE = 'maintenance';
    case OFFLINE = 'offline';
    case RETIRED = 'retired';
}
