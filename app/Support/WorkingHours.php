<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * A weekly set of working intervals, expressed in minutes from midnight.
 *
 * Intervals are merged on construction, so a day configured as 08:00-12:00 plus
 * 11:30-18:00 behaves as a single 08:00-18:00 window.
 */
final class WorkingHours
{
    public const MINUTES_IN_DAY = 1440;

    /**
     * @param  array<int, array<int, array{start: int, end: int}>>  $ranges  merged ranges keyed by weekday
     */
    private function __construct(private readonly array $ranges) {}

    /**
     * @param  iterable<int, array{weekday: int, start: int, end: int}>  $ranges
     */
    public static function fromRanges(iterable $ranges): self
    {
        $byWeekday = [];

        foreach ($ranges as $range) {
            $start = max(0, (int) $range['start']);
            $end = min(self::MINUTES_IN_DAY, (int) $range['end']);

            if ($end <= $start) {
                continue;
            }

            $byWeekday[(int) $range['weekday']][] = ['start' => $start, 'end' => $end];
        }

        return new self(array_map(self::mergeRanges(...), $byWeekday));
    }

    public function isEmpty(): bool
    {
        return $this->ranges === [];
    }

    /**
     * @return array<int, array{start: int, end: int}>
     */
    public function forWeekday(int $weekday): array
    {
        return $this->ranges[$weekday] ?? [];
    }

    /**
     * Earliest start and latest end across every configured weekday, or null
     * when nothing is configured.
     *
     * @return array{0: int, 1: int}|null
     */
    public function span(): ?array
    {
        if ($this->isEmpty()) {
            return null;
        }

        $starts = [];
        $ends = [];

        foreach ($this->ranges as $dayRanges) {
            foreach ($dayRanges as $range) {
                $starts[] = $range['start'];
                $ends[] = $range['end'];
            }
        }

        return [min($starts), max($ends)];
    }

    /**
     * Whether the whole interval falls inside a single working window.
     */
    public function covers(Carbon $startsAt, Carbon $endsAt): bool
    {
        if ($endsAt->lessThanOrEqualTo($startsAt)) {
            return true;
        }

        foreach ($this->windowsBetween($startsAt, $endsAt) as [$windowStart, $windowEnd]) {
            if ($windowStart->lessThanOrEqualTo($startsAt) && $windowEnd->greaterThanOrEqualTo($endsAt)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Minute ranges of the given day, bounded by [$from, $to], that fall outside
     * the working hours.
     *
     * @return array<int, array{start: int, end: int}>
     */
    public function gapsOn(Carbon $date, int $from, int $to): array
    {
        $gaps = [];
        $cursor = $from;

        foreach ($this->forWeekday($date->dayOfWeek) as $range) {
            if ($range['end'] <= $from || $range['start'] >= $to) {
                continue;
            }

            $start = max($range['start'], $from);

            if ($start > $cursor) {
                $gaps[] = ['start' => $cursor, 'end' => $start];
            }

            $cursor = max($cursor, min($range['end'], $to));
        }

        if ($cursor < $to) {
            $gaps[] = ['start' => $cursor, 'end' => $to];
        }

        return $gaps;
    }

    /**
     * Absolute working windows covering every day the interval touches.
     *
     * @return array<int, array{0: Carbon, 1: Carbon}>
     */
    private function windowsBetween(Carbon $startsAt, Carbon $endsAt): array
    {
        $windows = [];
        $day = $startsAt->copy()->startOfDay();

        while ($day->lessThan($endsAt)) {
            foreach ($this->forWeekday($day->dayOfWeek) as $range) {
                $windows[] = [
                    $day->copy()->addMinutes($range['start']),
                    $day->copy()->addMinutes($range['end']),
                ];
            }

            $day->addDay();
        }

        usort($windows, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        $merged = [];

        foreach ($windows as $window) {
            $last = array_key_last($merged);

            if ($last !== null && $window[0]->lessThanOrEqualTo($merged[$last][1])) {
                $merged[$last][1] = $window[1]->greaterThan($merged[$last][1]) ? $window[1] : $merged[$last][1];

                continue;
            }

            $merged[] = $window;
        }

        return $merged;
    }

    /**
     * @param  array<int, array{start: int, end: int}>  $ranges
     * @return array<int, array{start: int, end: int}>
     */
    private static function mergeRanges(array $ranges): array
    {
        usort($ranges, fn (array $a, array $b): int => $a['start'] <=> $b['start']);

        $merged = [];

        foreach ($ranges as $range) {
            $last = array_key_last($merged);

            if ($last !== null && $range['start'] <= $merged[$last]['end']) {
                $merged[$last]['end'] = max($merged[$last]['end'], $range['end']);

                continue;
            }

            $merged[] = $range;
        }

        return $merged;
    }
}
