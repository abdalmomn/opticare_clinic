<?php

namespace App\Modules\Core\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

/**
 * Eye laterality used by imaging files, imaging request items and
 * medical-record image filters.
 *
 * OD = right eye, OS = left eye, OU = both eyes.
 */
enum EyeEnum: string
{
    use HasEnumValues;

    case OD = 'OD';
    case OS = 'OS';
    case OU = 'OU';
}
