<?php

namespace App\Modules\Imaging\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum ImagingPaymentStatusEnum: string
{
    use HasEnumValues;

    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case WAIVED = 'waived';
    case REFUNDED = 'refunded';
}
