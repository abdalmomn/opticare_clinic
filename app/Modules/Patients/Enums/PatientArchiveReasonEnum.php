<?php

namespace App\Modules\Patients\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum PatientArchiveReasonEnum: string
{
    use HasEnumValues;

    case NO_LONGER_PATIENT = 'no_longer_patient';
    case TRANSFERRED = 'transferred';
    case DUPLICATE = 'duplicate';
    case DECEASED = 'deceased';
    case OTHER = 'other';
}
