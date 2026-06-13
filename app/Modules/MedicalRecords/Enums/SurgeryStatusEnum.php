<?php

namespace App\Modules\MedicalRecords\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum SurgeryStatusEnum: string
{
    use HasEnumValues;

    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELED = 'canceled';
}
