<?php

namespace App\Modules\Imaging\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum ImagingRequestPriorityEnum: string
{
    use HasEnumValues;

    case NORMAL = 'normal';
    case URGENT = 'urgent';
}
