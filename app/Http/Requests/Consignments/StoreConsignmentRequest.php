<?php

namespace App\Http\Requests\Consignments;

use App\Enums\CommissionType;
use App\Enums\ConsignmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('consignments.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $commissionType = (string) $this->input('commission_type');

        return [
            'vehicle_id' => ['required', 'integer', Rule::exists('vehicles', 'id')],
            'vehicle_owner_id' => ['required', 'integer', Rule::exists('vehicle_owners', 'id')],
            'status' => ['sometimes', Rule::in([ConsignmentStatus::Draft->value, ConsignmentStatus::Active->value])],
            'received_at' => ['required', 'date'],
            'expected_review_date' => ['nullable', 'date', 'after_or_equal:received_at'],
            'duration_days' => ['nullable', 'integer', 'min:1', 'max:1095'],
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
            'remarks' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
