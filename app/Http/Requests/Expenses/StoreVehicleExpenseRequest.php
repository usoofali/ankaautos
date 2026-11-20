<?php

namespace App\Http\Requests\Expenses;

use App\Enums\ExpenseBearer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVehicleExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('expenses.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $payImmediately = $this->boolean('pay_immediately');

        return [
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')],
            'borne_by' => ['required', Rule::enum(ExpenseBearer::class)],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'expense_date' => ['required', 'date'],
            'vendor_name' => ['nullable', 'string', 'max:150'],
            'receipt' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
            'pay_immediately' => ['sometimes', 'boolean'],
            'payment_method_id' => [$payImmediately ? 'required' : 'nullable', 'integer', Rule::exists('payment_methods', 'id')],
            'financial_account_id' => [$payImmediately ? 'required' : 'nullable', 'integer', Rule::exists('financial_accounts', 'id')],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ];
    }
}
