<?php

namespace App\Modules\MedicalRecords\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum VisitRecordStatusEnum: string
{
    use HasEnumValues;

    case DRAFT = 'draft';
    case FINALIZED = 'finalized';
    case CANCELLED = 'cancelled';
}
