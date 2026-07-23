<?php

namespace App\Http\Requests;

use App\Support\TimeOfDay;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'days' => ['present', 'array', 'max:7'],
            'days.*.weekday' => ['required', 'integer', 'between:0,6'],
            'days.*.ranges' => ['present', 'array', 'max:10'],
            'days.*.ranges.*.start' => ['required', 'date_format:H:i'],
            'days.*.ranges.*.end' => ['required', 'date_format:H:i'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ((array) $this->input('days', []) as $index => $day) {
                $this->validateDay($validator, (int) $index, (array) ($day['ranges'] ?? []));
            }
        });
    }

    /**
     * Rows ready to be persisted as employee schedules.
     *
     * @return array<int, array{weekday: int, start_minutes: int, end_minutes: int}>
     */
    public function scheduleRows(): array
    {
        $rows = [];

        foreach ($this->validated('days') as $day) {
            foreach ($day['ranges'] ?? [] as $range) {
                $rows[] = [
                    'weekday' => (int) $day['weekday'],
                    'start_minutes' => TimeOfDay::toMinutes($range['start']),
                    'end_minutes' => TimeOfDay::toMinutes($range['end']),
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  array<int, array{start?: string, end?: string}>  $ranges
     */
    private function validateDay(Validator $validator, int $index, array $ranges): void
    {
        $taken = [];

        foreach ($ranges as $position => $range) {
            if (! isset($range['start'], $range['end'])) {
                continue;
            }

            $start = TimeOfDay::toMinutes($range['start']);
            $end = TimeOfDay::toMinutes($range['end']);
            $key = "days.{$index}.ranges.{$position}.end";

            if ($end <= $start) {
                $validator->errors()->add($key, 'O fim deve ser depois do início.');

                continue;
            }

            foreach ($taken as [$otherStart, $otherEnd]) {
                if ($start < $otherEnd && $end > $otherStart) {
                    $validator->errors()->add($key, 'Este intervalo se sobrepõe a outro do mesmo dia.');

                    continue 2;
                }
            }

            $taken[] = [$start, $end];
        }
    }
}
