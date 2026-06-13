<?php

namespace App\Modules\Appointments\Enums;

use App\Modules\Core\Enums\Concerns\HasEnumValues;

enum AppointmentSourceEnum: string
{
    use HasEnumValues;

    case SECRETARY = 'secretary';
    case DOCTOR = 'doctor';
    case PATIENT_APP = 'patient_app';
    case SYSTEM = 'system';
}
