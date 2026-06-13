<?php

namespace App\Modules\MedicalRecords\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum VisitTypeEnum: string
{
    use HasEnumValues;

    case CONSULTATION = 'consultation';
    case FOLLOW_UP = 'follow_up';
    case EMERGENCY = 'emergency';
    case POST_OP = 'post_op';
}
