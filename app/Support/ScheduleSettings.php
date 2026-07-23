<?php

namespace App\Support;

class ScheduleSettings
{
    /**
     * Fallback bounds used when no employee has a configured shift, and for
     * forms that offer a fixed list of times.
     */
    public const START_HOUR = 7;

    public const END_HOUR = 21;

    public const SLOT_MINUTES = 15;

    public const LINE_MINUTES = 30;

    /**
     * Shortest grid the schedule may collapse to, so a single short shift
     * still renders a usable day.
     */
    public const MIN_SPAN_HOURS = 4;

    /**
     * @return array{start_hour: int, end_hour: int, slot_minutes: int, line_minutes: int}
     */
    public static function toArray(): array
    {
        return self::forSpan(self::START_HOUR * 60, self::END_HOUR * 60);
    }

    /**
     * Grid settings covering the given minute span, rounded out to whole hours.
     *
     * @return array{start_hour: int, end_hour: int, slot_minutes: int, line_minutes: int}
     */
    public static function forSpan(int $startMinutes, int $endMinutes): array
    {
        $startHour = max(0, intdiv($startMinutes, 60));
        $endHour = min(24, (int) ceil($endMinutes / 60));

        if ($endHour - $startHour < self::MIN_SPAN_HOURS) {
            $endHour = min(24, $startHour + self::MIN_SPAN_HOURS);
            $startHour = max(0, $endHour - self::MIN_SPAN_HOURS);
        }

        return [
            'start_hour' => $startHour,
            'end_hour' => $endHour,
            'slot_minutes' => self::SLOT_MINUTES,
            'line_minutes' => self::LINE_MINUTES,
        ];
    }
}
