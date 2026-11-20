<?php

namespace App\Http\Requests\Admin;

use App\Enums\ChargeTransactionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MasterDataItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user === null) {
            return false;
        }

        $type = (string) $this->route('type');
        if ($type === 'charge_categories') {
            return $user->can('charges.manage_categories') || $user->can('master_data.manage');
        }

        return $user->can('master_data.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = (string) $this->route('type');
        $id = $this->route('id');

        $uniqueName = fn (string $table): mixed => Rule::unique($table, 'name')->ignore($id);

        return match ($type) {
            'brands' => [
                'name' => ['required', 'string', 'max:100', $uniqueName('vehicle_brands')],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'models' => [
                'vehicle_brand_id' => ['required', 'integer', Rule::exists('vehicle_brands', 'id')],
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('vehicle_models', 'name')
                        ->where('vehicle_brand_id', $this->input('vehicle_brand_id'))
                        ->ignore($id),
                ],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'categories' => [
                'name' => ['required', 'string', 'max:100', $uniqueName('vehicle_categories')],
                'description' => ['nullable', 'string', 'max:500'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'conditions' => [
                'name' => ['required', 'string', 'max:100', $uniqueName('vehicle_conditions')],
                'description' => ['nullable', 'string', 'max:500'],
                'severity_order' => ['required', 'integer', 'min:1', 'max:100'],
                'badge_variant' => ['required', 'string', Rule::in(['success', 'info', 'warning', 'danger', 'neutral'])],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'expense_categories' => [
                'name' => ['required', 'string', 'max:100', $uniqueName('expense_categories')],
                'description' => ['nullable', 'string', 'max:500'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'charge_categories' => [
                'name' => ['required', 'string', 'max:100', $uniqueName('charge_categories')],
                'description' => ['nullable', 'string', 'max:500'],
                'applicable_types' => ['nullable', 'array'],
                'applicable_types.*' => ['string', Rule::in(array_column(ChargeTransactionType::cases(), 'value'))],
                'default_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'payment_methods' => [
                'name' => ['required', 'string', 'max:100', $uniqueName('payment_methods')],
                'code' => ['required', 'string', 'max:40', 'alpha_dash', Rule::unique('payment_methods', 'code')->ignore($id)],
                'requires_reference' => ['sometimes', 'boolean'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'financial_accounts' => [
                'name' => ['required', 'string', 'max:120', $uniqueName('financial_accounts')],
                'bank_name' => ['nullable', 'string', 'max:120'],
                'account_number' => ['nullable', 'string', 'max:40'],
                'account_type' => ['required', 'string', Rule::in(['bank', 'cash', 'mobile_money'])],
                'is_active' => ['sometimes', 'boolean'],
            ],
            'document_categories' => [
                'name' => ['required', 'string', 'max:100', $uniqueName('document_categories')],
                'description' => ['nullable', 'string', 'max:500'],
                'is_private' => ['sometimes', 'boolean'],
                'is_active' => ['sometimes', 'boolean'],
            ],
            default => [],
        };
    }
}
