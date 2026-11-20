<?php

namespace App\Http\Requests\Expenses;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordExpensePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('expenses.pay') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')],
            'financial_account_id' => ['required', 'integer', Rule::exists('financial_accounts', 'id')],
            'paid_at' => ['required', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
