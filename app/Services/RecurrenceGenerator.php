<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\NotificationSetting;
use App\Models\Recurrence;
use Illuminate\Support\Carbon;

class RecurrenceGenerator
{
    public function __construct(private AvailabilityService $availability) {}

    /**
     * @return array{created: int, skipped_existing: int, skipped_conflicts: int, skipped_unavailable: int}
     */
    public function generate(Recurrence $recurrence, ?int $horizonDays = null): array
    {
        $summary = self::emptySummary();

        if (! $recurrence->active) {
            return $summary;
        }

        $recurrence->loadMissing('product');
        $service = $recurrence->product;

        if ($service === null) {
            return $summary;
        }

        $horizon = $horizonDays ?? NotificationSetting::current()->recurrence_horizon_days;
        $duration = max(5, (int) $service->duration_minutes);
        $price = (float) $service->price;
        $time = substr((string) $recurrence->time, 0, 5);

        $today = Carbon::today();
        $occurrences = $recurrence->occurrencesBetween($today, $today->copy()->addDays($horizon));

        foreach ($occurrences as $date) {
            $startsAt = Carbon::parse($date->toDateString().' '.$time);

            if ($startsAt->isPast()) {
                continue;
            }

            $endsAt = $startsAt->copy()->addMinutes($duration);

            if ($this->alreadyGenerated($recurrence, $startsAt)) {
                $summary['skipped_existing']++;

                continue;
            }

            if (Appointment::query()->overlapping($recurrence->employee_id, $startsAt, $endsAt)->exists()) {
                $summary['skipped_conflicts']++;

                continue;
            }

            if ($this->availability->reasonFor($recurrence->employee_id, $startsAt, $endsAt) !== null) {
                $summary['skipped_unavailable']++;

                continue;
            }

            Appointment::create([
                'customer_id' => $recurrence->customer_id,
                'employee_id' => $recurrence->employee_id,
                'product_id' => $recurrence->product_id,
                'starts_at' => $startsAt,
                'ends_at' => $endsAt,
                'status' => AppointmentStatus::Scheduled->value,
                'price' => $price,
                'origin' => 'recurrence',
                'recurrence_id' => $recurrence->id,
            ]);

            $summary['created']++;
        }

        return $summary;
    }

    /**
     * @return array{created: int, skipped_existing: int, skipped_conflicts: int, skipped_unavailable: int}
     */
    public static function emptySummary(): array
    {
        return ['created' => 0, 'skipped_existing' => 0, 'skipped_conflicts' => 0, 'skipped_unavailable' => 0];
    }

    public function clearFutureScheduled(Recurrence $recurrence): int
    {
        return Appointment::query()
            ->where('recurrence_id', $recurrence->id)
            ->where('origin', 'recurrence')
            ->where('status', AppointmentStatus::Scheduled->value)
            ->where('starts_at', '>', now())
            ->delete();
    }

    private function alreadyGenerated(Recurrence $recurrence, Carbon $startsAt): bool
    {
        return Appointment::query()
            ->where('recurrence_id', $recurrence->id)
            ->where('starts_at', $startsAt)
            ->exists();
    }
}
