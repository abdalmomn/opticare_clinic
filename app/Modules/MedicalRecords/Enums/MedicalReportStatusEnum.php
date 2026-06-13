<?php

namespace App\Modules\MedicalRecords\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum MedicalReportStatusEnum: string
{
    use HasEnumValues;

    case DRAFT = 'draft';
    case FINALIZED = 'finalized';
}
