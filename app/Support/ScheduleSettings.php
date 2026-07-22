<?php

namespace App\Support;

class ScheduleSettings
{
    public const START_HOUR = 7;

    public const END_HOUR = 21;

    public const SLOT_MINUTES = 15;

    public const LINE_MINUTES = 30;

    /**
     * @return array{start_hour: int, end_hour: int, slot_minutes: int, line_minutes: int}
     */
    public static function toArray(): array
    {
        return [
            'start_hour' => self::START_HOUR,
            'end_hour' => self::END_HOUR,
            'slot_minutes' => self::SLOT_MINUTES,
            'line_minutes' => self::LINE_MINUTES,
        ];
    }
}
