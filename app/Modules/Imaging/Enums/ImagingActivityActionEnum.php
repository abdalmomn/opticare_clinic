<?php

namespace App\Modules\Imaging\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum ImagingActivityActionEnum: string
{
    use HasEnumValues;

    case REQUEST_CREATED = 'request_created';
    case REQUEST_CANCELLED = 'request_cancelled';
    case PAYMENT_CONFIRMED = 'payment_confirmed';
    case PAYMENT_WAIVED = 'payment_waived';
    case SENT_TO_TECHNICIAN = 'sent_to_technician';
    case STARTED = 'started';
    case FILE_UPLOADED = 'file_uploaded';
    case FILE_DELETED = 'file_deleted';
    case COMPLETED = 'completed';
    case DIRECT_UPLOAD_CREATED = 'direct_upload_created';
    case EXTERNAL_UPLOAD_CREATED = 'external_upload_created';
    case DEVICE_CREATED = 'device_created';
    case DEVICE_UPDATED = 'device_updated';
    case DEVICE_ACTIVATED = 'device_activated';
    case DEVICE_DEACTIVATED = 'device_deactivated';
    case DEVICE_DELETED_OR_RETIRED = 'device_deleted_or_retired';
}
