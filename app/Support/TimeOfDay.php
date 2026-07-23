<?php

namespace App\Support;

/**
 * Conversions between `H:i` strings and minutes from midnight.
 */
class TimeOfDay
{
    public static function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map(intval(...), explode(':', $time));

        return $hours * 60 + $minutes;
    }

    public static function toString(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
