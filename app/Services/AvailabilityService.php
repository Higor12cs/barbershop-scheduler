<?php

namespace App\Services;

use App\Models\EmployeeSchedule;
use App\Models\ScheduleBlock;
use App\Support\ScheduleSettings;
use App\Support\WorkingHours;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * @var array<int, WorkingHours>
     */
    private array $cache = [];

    /**
     * Why the employee cannot take an appointment in the given interval, or
     * null when the slot is fine.
     *
     * Employees without any configured working hours are treated as available
     * all day, so the feature stays opt-in.
     */
    public function reasonFor(int $employeeId, Carbon $startsAt, Carbon $endsAt): ?string
    {
        $block = ScheduleBlock::query()->overlapping($employeeId, $startsAt, $endsAt)->first();

        if ($block !== null) {
            return "Período bloqueado na agenda: {$block->label()}.";
        }

        $hours = $this->workingHours($employeeId);

        if (! $hours->isEmpty() && ! $hours->covers($startsAt, $endsAt)) {
            return 'Horário fora da jornada de trabalho do funcionário.';
        }

        return null;
    }

    public function workingHours(int $employeeId): WorkingHours
    {
        return $this->workingHoursFor([$employeeId])[$employeeId];
    }

    /**
     * Grid settings spanning the shifts of the given employees, so the day
     * starts at the earliest shift and ends at the latest one.
     *
     * An employee with no configured shift can be booked at any time, so they
     * widen the grid back to the default bounds. Ranges in `$mustInclude` —
     * existing appointments, which may have been forced outside the shift —
     * are always kept visible.
     *
     * @param  array<int, int>  $employeeIds
     * @param  array<int, array{start: int, end: int}>  $mustInclude
     * @return array{start_hour: int, end_hour: int, slot_minutes: int, line_minutes: int}
     */
    public function gridSettings(array $employeeIds, array $mustInclude = []): array
    {
        $starts = [];
        $ends = [];

        foreach ($this->workingHoursFor($employeeIds) as $hours) {
            $span = $hours->span() ?? [ScheduleSettings::START_HOUR * 60, ScheduleSettings::END_HOUR * 60];

            $starts[] = $span[0];
            $ends[] = $span[1];
        }

        foreach ($mustInclude as $range) {
            $starts[] = $range['start'];
            $ends[] = $range['end'];
        }

        if ($starts === []) {
            return ScheduleSettings::toArray();
        }

        return ScheduleSettings::forSpan(min($starts), max($ends));
    }

    /**
     * Unavailable minute ranges per employee and date, used to paint the
     * schedule grid. Only entries with something to paint are returned.
     *
     * @param  array<int, int>  $employeeIds
     * @param  array<int, string>  $dates
     * @return array<int, array{employee_id: int, date: string, ranges: array<int, array{start: int, end: int, type: string, label: string}>}>
     */
    public function calendar(array $employeeIds, array $dates, int $gridStart, int $gridEnd): array
    {
        if ($employeeIds === [] || $dates === []) {
            return [];
        }

        $hours = $this->workingHoursFor($employeeIds);
        $blocks = $this->blocksBetween($employeeIds, $dates);
        $calendar = [];

        foreach ($employeeIds as $employeeId) {
            foreach ($dates as $date) {
                $day = Carbon::parse($date)->startOfDay();
                $ranges = [];

                foreach ($hours[$employeeId]->gapsOn($day, $gridStart, $gridEnd) as $gap) {
                    $ranges[] = [...$gap, 'type' => 'off', 'label' => 'Fora da jornada'];
                }

                foreach ($blocks as $block) {
                    if ($block->employee_id !== null && $block->employee_id !== $employeeId) {
                        continue;
                    }

                    $range = $this->clipToDay($block, $day, $gridStart, $gridEnd);

                    if ($range !== null) {
                        $ranges[] = [...$range, 'type' => 'block', 'label' => $block->label()];
                    }
                }

                if ($ranges !== []) {
                    $calendar[] = ['employee_id' => $employeeId, 'date' => $date, 'ranges' => $ranges];
                }
            }
        }

        return $calendar;
    }

    /**
     * @param  array<int, int>  $employeeIds
     * @return array<int, WorkingHours>
     */
    private function workingHoursFor(array $employeeIds): array
    {
        $missing = array_values(array_diff($employeeIds, array_keys($this->cache)));

        if ($missing !== []) {
            $grouped = EmployeeSchedule::query()
                ->whereIn('employee_id', $missing)
                ->get(['employee_id', 'weekday', 'start_minutes', 'end_minutes'])
                ->groupBy('employee_id');

            foreach ($missing as $employeeId) {
                $this->cache[$employeeId] = WorkingHours::fromRanges(
                    $grouped->get($employeeId, new Collection)
                        ->map(fn (EmployeeSchedule $schedule): array => [
                            'weekday' => $schedule->weekday,
                            'start' => $schedule->start_minutes,
                            'end' => $schedule->end_minutes,
                        ])
                        ->all(),
                );
            }
        }

        return array_intersect_key($this->cache, array_flip($employeeIds));
    }

    /**
     * @param  array<int, int>  $employeeIds
     * @param  array<int, string>  $dates
     * @return Collection<int, ScheduleBlock>
     */
    private function blocksBetween(array $employeeIds, array $dates): Collection
    {
        $from = Carbon::parse(min($dates))->startOfDay();
        $to = Carbon::parse(max($dates))->startOfDay()->addDay();

        return ScheduleBlock::query()
            ->where('starts_at', '<', $to)
            ->where('ends_at', '>', $from)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('employee_id')
                ->orWhereIn('employee_id', $employeeIds))
            ->get();
    }

    /**
     * @return array{start: int, end: int}|null
     */
    private function clipToDay(ScheduleBlock $block, Carbon $day, int $gridStart, int $gridEnd): ?array
    {
        $dayEnd = $day->copy()->addDay();

        if ($block->starts_at->greaterThanOrEqualTo($dayEnd) || $block->ends_at->lessThanOrEqualTo($day)) {
            return null;
        }

        $start = $block->starts_at->lessThanOrEqualTo($day)
            ? 0
            : $block->starts_at->hour * 60 + $block->starts_at->minute;

        $end = $block->ends_at->greaterThanOrEqualTo($dayEnd)
            ? WorkingHours::MINUTES_IN_DAY
            : $block->ends_at->hour * 60 + $block->ends_at->minute;

        $start = max($start, $gridStart);
        $end = min($end, $gridEnd);

        return $end > $start ? ['start' => $start, 'end' => $end] : null;
    }
}
