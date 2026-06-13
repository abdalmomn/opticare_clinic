<?php

namespace App\Modules\Imaging\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum ImagingRequestSourceEnum: string
{
    use HasEnumValues;

    case DOCTOR_REQUEST = 'doctor_request';
    case SECRETARY_REQUEST = 'secretary_request';
    case DOCTOR_UPLOAD = 'doctor_upload';
    case EXTERNAL = 'external';
}
