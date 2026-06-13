<?php

namespace App\Modules\Payments\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum InvoiceStatusEnum: string
{
    use HasEnumValues;

    case UNPAID = 'unpaid';
    case PARTIAL = 'partial';
    case PAID = 'paid';
    case CANCELED = 'canceled';
}
