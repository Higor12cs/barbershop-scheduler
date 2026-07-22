<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use App\Support\ScheduleSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecurrenceRequest extends FormRequest
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
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')->where('active', true)],
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('active', true)],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('active', true)->where('type', ProductType::Service->value)],
            'time' => ['required', 'date_format:H:i', Rule::in($this->gridSlots())],
            'interval_days' => ['required', 'integer', 'min:1', 'max:365'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string'],
            'active' => ['boolean'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function gridSlots(): array
    {
        $slots = [];

        for ($hour = ScheduleSettings::START_HOUR; $hour < ScheduleSettings::END_HOUR; $hour++) {
            for ($minute = 0; $minute < 60; $minute += ScheduleSettings::SLOT_MINUTES) {
                $slots[] = sprintf('%02d:%02d', $hour, $minute);
            }
        }

        return $slots;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'employee_id' => 'funcionário',
            'product_id' => 'serviço',
            'time' => 'horário',
            'interval_days' => 'intervalo',
            'starts_on' => 'data de início',
            'ends_on' => 'data limite',
            'notes' => 'observações',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Selecione um cliente.',
            'customer_id.exists' => 'Selecione um cliente ativo.',
            'employee_id.required' => 'Selecione um funcionário.',
            'employee_id.exists' => 'Selecione um funcionário ativo.',
            'product_id.required' => 'Selecione um serviço.',
            'product_id.exists' => 'Selecione um serviço ativo.',
            'time.required' => 'Informe o horário.',
            'time.in' => 'O horário deve estar dentro da grade da agenda.',
            'interval_days.min' => 'O intervalo deve ser de no mínimo 1 dia.',
            'interval_days.max' => 'O intervalo deve ser de no máximo 365 dias.',
            'ends_on.after_or_equal' => 'A data limite deve ser igual ou posterior à data de início.',
        ];
    }
}
