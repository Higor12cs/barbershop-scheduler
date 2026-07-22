<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('active', true)],
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('active', true)],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'cliente',
            'employee_id' => 'funcionário',
            'date' => 'data',
            'time' => 'horário',
            'items' => 'itens',
            'items.*.product_id' => 'produto',
            'items.*.quantity' => 'quantidade',
            'items.*.unit_price' => 'preço unitário',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Adicione ao menos um item à venda.',
            'items.min' => 'Adicione ao menos um item à venda.',
            'items.*.product_id.required' => 'Selecione o produto ou serviço.',
            'items.*.product_id.exists' => 'Selecione um produto ativo.',
            'items.*.quantity.min' => 'A quantidade deve ser no mínimo 1.',
        ];
    }
}
