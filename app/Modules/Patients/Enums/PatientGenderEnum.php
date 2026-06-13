<?php

namespace App\Modules\Patients\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum PatientGenderEnum: string
{
    use HasEnumValues;

    case MALE = 'male';
    case FEMALE = 'female';
}
