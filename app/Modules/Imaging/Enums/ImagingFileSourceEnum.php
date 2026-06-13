<?php

namespace App\Modules\Imaging\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum ImagingFileSourceEnum: string
{
    use HasEnumValues;

    case TECHNICIAN_UPLOAD = 'technician_upload';
    case DOCTOR_UPLOAD = 'doctor_upload';
    case EXTERNAL = 'external';
}
