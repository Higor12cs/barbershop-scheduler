<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * The daily window in which customers may be messaged, expressed in minutes
 * from midnight.
 */
final class NotificationWindow
{
    public const DEFAULT_START = 480;

    public const DEFAULT_END = 1200;

    /**
     * Upper bound for how far a send can be pulled backwards when it would fall
     * outside the window. Used to size the "might be due" lookahead.
     */
    private const MAX_SHIFT_MINUTES = WorkingHours::MINUTES_IN_DAY;

    public function __construct(
        private readonly int $startMinutes = self::DEFAULT_START,
        private readonly int $endMinutes = self::DEFAULT_END,
    ) {}

    public function isOpen(Carbon $at): bool
    {
        $minutes = $at->hour * 60 + $at->minute;

        return $minutes >= $this->startMinutes && $minutes <= $this->endMinutes;
    }

    /**
     * When a message configured to go out `$minutesBefore` the appointment
     * should actually be sent.
     *
     * A send that lands outside the window is always pulled backwards to the
     * last allowed minute, never pushed forward: an 08:00 appointment with a
     * two-hour lead time is announced at 20:00 the previous day, because a
     * reminder delivered after the appointment is worthless.
     */
    public function dueAt(Carbon $startsAt, int $minutesBefore): Carbon
    {
        $due = $startsAt->copy()->subMinutes($minutesBefore);
        $minutes = $due->hour * 60 + $due->minute;

        if ($minutes > $this->endMinutes) {
            return $due->copy()->startOfDay()->addMinutes($this->endMinutes);
        }

        if ($minutes < $this->startMinutes) {
            return $due->copy()->subDay()->startOfDay()->addMinutes($this->endMinutes);
        }

        return $due;
    }

    /**
     * How far ahead to scan for appointments whose message may already be due.
     */
    public function lookaheadMinutes(int $minutesBefore): int
    {
        return $minutesBefore + self::MAX_SHIFT_MINUTES;
    }
}
