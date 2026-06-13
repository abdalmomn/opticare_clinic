<?php

namespace App\Modules\MedicalRecords\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum DoctorNoteVisibilityEnum: string
{
    use HasEnumValues;

    case PRIVATE = 'private';
    case SHARED_INTERNAL = 'shared_internal';
}
