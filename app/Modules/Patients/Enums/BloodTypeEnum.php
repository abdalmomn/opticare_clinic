<?php

namespace App\Modules\Patients\Enums;

enum BloodTypeEnum: string
{
    case A_POSITIVE = 'A+';
    case A_NEGATIVE = 'A-';

    case B_POSITIVE = 'B+';
    case B_NEGATIVE = 'B-';

    case AB_POSITIVE = 'AB+';
    case AB_NEGATIVE = 'AB-';

    case O_POSITIVE = 'O+';
    case O_NEGATIVE = 'O-';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
