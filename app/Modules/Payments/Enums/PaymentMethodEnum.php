<?php

namespace App\Modules\Payments\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum PaymentMethodEnum: string
{
    use HasEnumValues;

    case CASH = 'cash';
    case CARD = 'card';
    case TRANSFER = 'transfer';
}
