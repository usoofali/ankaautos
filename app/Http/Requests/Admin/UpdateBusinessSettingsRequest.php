<?php

namespace App\Http\Requests\Admin;

use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('settings.manage') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:150'],
            'tagline' => ['nullable', 'string', 'max:200'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:40'],
            'whatsapp_number' => ['required', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'email' => ['required', 'email', 'max:150'],
            'currency_code' => ['required', 'string', 'size:3', 'alpha'],
            'currency_symbol' => ['required', 'string', 'max:10'],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'reference_prefix' => ['required', 'string', 'min:2', 'max:8', 'alpha_num'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
        ];
    }
}
