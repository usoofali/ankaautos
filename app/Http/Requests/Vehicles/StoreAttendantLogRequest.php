<?php

namespace App\Http\Requests\Vehicles;

use App\Enums\AttendantActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendantLogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('vehicles.log_attendant_activity') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'activity_type' => ['required', Rule::enum(AttendantActivityType::class)],
            'description' => ['required', 'string', 'max:2000'],
            'location_from' => ['nullable', 'string', 'max:120'],
            'location_to' => ['nullable', 'string', 'max:120'],
            'update_vehicle_location' => ['sometimes', 'boolean'],
        ];
    }
}
