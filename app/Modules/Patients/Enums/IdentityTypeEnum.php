<?php

namespace App\Modules\Patients\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum IdentityTypeEnum: string
{
    use HasEnumValues;

    case NATIONAL_ID = 'national_id';
    case PASSPORT = 'passport';
}
