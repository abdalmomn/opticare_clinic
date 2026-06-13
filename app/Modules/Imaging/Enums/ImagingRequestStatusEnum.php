<?php

namespace App\Modules\Imaging\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

/**
 * Canonical imaging request workflow statuses.
 *
 * Legacy values 'pending' and 'canceled' are intentionally NOT part of this
 * enum; they are normalized to pending_payment / cancelled by
 * ImagingRequest::normalizeStatus().
 */
enum ImagingRequestStatusEnum: string
{
    use HasEnumValues;

    case REQUESTED = 'requested';
    case PENDING_PAYMENT = 'pending_payment';
    case PAYMENT_CONFIRMED = 'payment_confirmed';
    case READY_FOR_IMAGING = 'ready_for_imaging';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
