<?php

namespace App\Http\Requests;

use App\Support\TimeOfDay;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class ScheduleBlockRequest extends FormRequest
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
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'all_day' => ['boolean'],
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('all_day')) {
                return;
            }

            if (blank($this->input('start_time')) || blank($this->input('end_time'))) {
                $validator->errors()->add('start_time', 'Informe o horário de início e de fim, ou marque o dia inteiro.');

                return;
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->endsAt()->lessThanOrEqualTo($this->startsAt())) {
                $validator->errors()->add('end_time', 'O fim do bloqueio deve ser depois do início.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function blockAttributes(): array
    {
        return [
            'employee_id' => $this->input('employee_id') ?: null,
            'all_day' => $this->boolean('all_day'),
            'reason' => $this->input('reason') ?: null,
            'starts_at' => $this->startsAt(),
            'ends_at' => $this->endsAt(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employee_id' => 'funcionário',
            'start_date' => 'data de início',
            'end_date' => 'data de fim',
            'start_time' => 'horário de início',
            'end_time' => 'horário de fim',
            'reason' => 'motivo',
        ];
    }

    private function startsAt(): Carbon
    {
        $date = Carbon::parse((string) $this->input('start_date'))->startOfDay();

        if ($this->boolean('all_day')) {
            return $date;
        }

        return $date->addMinutes(TimeOfDay::toMinutes((string) $this->input('start_time')));
    }

    private function endsAt(): Carbon
    {
        $date = Carbon::parse((string) $this->input('end_date'))->startOfDay();

        if ($this->boolean('all_day')) {
            return $date->addDay();
        }

        return $date->addMinutes(TimeOfDay::toMinutes((string) $this->input('end_time')));
    }
}
