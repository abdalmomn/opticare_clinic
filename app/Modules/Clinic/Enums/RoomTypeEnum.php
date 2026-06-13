<?php

namespace App\Modules\Clinic\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum RoomTypeEnum: string
{
    use HasEnumValues;

    case IMAGING = 'imaging';
    case CLINIC = 'clinic';
    case SURGERY = 'surgery';
    case LAB = 'lab';
    case RECEPTION = 'reception';
    case EXTERNAL_CENTER = 'external_center';
}
