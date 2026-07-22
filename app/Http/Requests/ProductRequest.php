<?php

namespace App\Http\Requests;

use App\Enums\ProductType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(ProductType::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'duration_minutes' => [
                'nullable',
                'integer',
                'min:1',
                Rule::requiredIf(fn (): bool => $this->input('type') === ProductType::Service->value),
            ],
            'description' => ['nullable', 'string'],
            'active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('type') !== ProductType::Service->value) {
            $this->merge(['duration_minutes' => null]);
        }
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'type' => 'tipo',
            'price' => 'preço',
            'cost' => 'custo',
            'duration_minutes' => 'duração',
            'description' => 'descrição',
        ];
    }

    public function messages(): array
    {
        return [
            'duration_minutes.required' => 'A duração é obrigatória para serviços.',
        ];
    }
}
