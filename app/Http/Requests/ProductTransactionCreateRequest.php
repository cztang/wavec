<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductTransaction;

class ProductTransactionCreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $transactionTypes = array_keys(ProductTransaction::transactionTypes());
        
        return [
            'transaction_type' => ['required', 'integer', 'in:' . implode(',', $transactionTypes)],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'cost_per_unit' => [
                'required_if:transaction_type,1', // Required only for purchase (type 1)
                'nullable',
                'numeric',
                'gt:0'
            ],
            'transaction_date' => ['required', 'date'],
        ];
    }

    /**
     * Get custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'transaction_type.required' => 'Transaction type is required.',
            'transaction_type.integer' => 'Transaction type must be a valid integer.',
            'transaction_type.in' => 'Transaction type must be a valid type (1 for purchase, 2 for sale).',
            'quantity.required' => 'Quantity is required.',
            'quantity.numeric' => 'Quantity must be a valid number.',
            'quantity.gt' => 'Quantity must be greater than 0.',
            'cost_per_unit.required_if' => 'Cost per unit is required for purchase transactions.',
            'cost_per_unit.numeric' => 'Cost per unit must be a valid number.',
            'cost_per_unit.gt' => 'Cost per unit must be greater than 0.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.date' => 'Transaction date must be a valid date.',
        ];
    }
}
