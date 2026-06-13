<?php

namespace App\Modules\Appointments\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum AppointmentTypeEnum: string
{
    use HasEnumValues;

    case CONSULTATION = 'consultation';
    case FOLLOW_UP = 'follow_up';
    case IMAGING = 'imaging';
    case CONSULTATION_AND_IMAGING = 'consultation_and_imaging';
    case SURGERY_PREPARATION = 'surgery_preparation';
}
