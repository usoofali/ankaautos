<?php

namespace App\Http\Requests\Consignments;

use App\Enums\CommissionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AmendConsignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('consignments.amend_terms') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $commissionType = (string) $this->input('commission_type');

        return [
            'agreed_selling_price' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'minimum_acceptable_price' => [
                $commissionType === CommissionType::ExcessOverMinimum->value ? 'required' : 'nullable',
                'numeric',
                'min:0',
                'max:999999999999.99',
            ],
            'commission_type' => ['required', Rule::enum(CommissionType::class)],
            'commission_value' => [
                in_array($commissionType, [CommissionType::Fixed->value, CommissionType::Percentage->value], true) ? 'required' : 'nullable',
                'numeric',
                'min:0',
                $commissionType === CommissionType::Percentage->value ? 'max:100' : 'max:999999999999.99',
            ],
            'commission_notes' => ['nullable', 'string', 'max:1000'],
            'expense_responsibility_notes' => ['nullable', 'string', 'max:1500'],
            'withdrawal_terms' => ['nullable', 'string', 'max:1500'],
            'settlement_terms' => ['nullable', 'string', 'max:1500'],
            'amendment_reason' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }
}
