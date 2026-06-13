<?php

namespace App\Modules\Patients\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum PatientStatusEnum: string
{
    use HasEnumValues;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case ARCHIVED = 'archived';
    case DECEASED = 'deceased';
}
