<?php

namespace App\Modules\Imaging\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum ImagingRequestItemStatusEnum: string
{
    use HasEnumValues;

    case REQUESTED = 'requested';
    case CAPTURED = 'captured';
    case SKIPPED = 'skipped';
}
