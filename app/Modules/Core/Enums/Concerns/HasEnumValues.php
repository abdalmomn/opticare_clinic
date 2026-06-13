<?php

namespace App\Modules\Core\Enums\Concerns;

trait HasEnumValues
{
    /**
     * Return the backing values of every case as a flat array.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
