<?php

namespace App\Modules\Patients\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum MaritalStatusEnum: string
{
    use HasEnumValues;

    case SINGLE = 'single';
    case MARRIED = 'married';
    case DIVORCED = 'divorced';
    case WIDOWED = 'widowed';
}
