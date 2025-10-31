<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\ProductTransaction;

class ProductTransactionEditRequest extends FormRequest
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
        // Get the transaction being edited to check its type
        $transactionId = $this->route('transactionId');
        $transaction = ProductTransaction::find($transactionId);
        
        $rules = [
            'quantity' => ['required', 'numeric', 'gt:0'],
        ];

        // Only require cost_per_unit for purchase transactions (type 1)
        if ($transaction && $transaction->transaction_type == 1) {
            $rules['cost_per_unit'] = ['required', 'numeric', 'gt:0'];
        } else {
            $rules['cost_per_unit'] = ['nullable', 'numeric', 'gt:0'];
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'quantity.required' => 'Quantity is required.',
            'quantity.numeric' => 'Quantity must be a valid number.',
            'quantity.gt' => 'Quantity must be greater than 0.',
            'cost_per_unit.required' => 'Cost per unit is required for purchase transactions.',
            'cost_per_unit.numeric' => 'Cost per unit must be a valid number.',
            'cost_per_unit.gt' => 'Cost per unit must be greater than 0.',
            'transaction_date.required' => 'Transaction date is required.',
            'transaction_date.date' => 'Transaction date must be a valid date.',
        ];
    }
}