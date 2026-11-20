<?php

namespace App\Http\Requests\Owners;

use App\Enums\OwnerCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleOwnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('owners.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owner_category' => ['required', Rule::enum(OwnerCategory::class)],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],
            'alt_phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'id_type' => ['nullable', 'string', 'max:60'],
            'id_number' => ['nullable', 'string', 'max:80'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'max:40'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
