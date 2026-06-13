<?php

namespace App\Modules\Appointments\Enums;

enum AppointmentStatusEnum: string
{
    case BOOKED = 'booked';
    case CONFIRMED = 'confirmed';
    case WAITING = 'waiting';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case NO_SHOW = 'no_show';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
