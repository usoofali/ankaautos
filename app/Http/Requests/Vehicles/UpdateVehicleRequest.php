<?php

namespace App\Http\Requests\Vehicles;

use App\Enums\DriveType;
use App\Enums\FuelType;
use App\Enums\SteeringPosition;
use App\Enums\TransmissionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('vehicles.edit') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxYear = (int) now()->format('Y') + 2;

        return [
            'vehicle_brand_id' => ['required', 'integer', Rule::exists('vehicle_brands', 'id')],
            'vehicle_model_id' => [
                'required',
                'integer',
                Rule::exists('vehicle_models', 'id')->where('vehicle_brand_id', $this->input('vehicle_brand_id')),
            ],
            'vehicle_category_id' => ['required', 'integer', Rule::exists('vehicle_categories', 'id')],
            'vehicle_condition_id' => ['required', 'integer', Rule::exists('vehicle_conditions', 'id')],
            'year' => ['required', 'integer', 'min:1970', "max:{$maxYear}"],
            'vin' => ['nullable', 'string', 'max:60'],
            'chassis_number' => ['nullable', 'string', 'max:80'],
            'engine_number' => ['nullable', 'string', 'max:80'],
            'registration_number' => ['nullable', 'string', 'max:40'],
            'transmission' => ['required', Rule::enum(TransmissionType::class)],
            'fuel_type' => ['required', Rule::enum(FuelType::class)],
            'drive_type' => ['nullable', Rule::enum(DriveType::class)],
            'exterior_colour' => ['required', 'string', 'max:60'],
            'interior_colour' => ['nullable', 'string', 'max:60'],
            'mileage' => ['nullable', 'integer', 'min:0', 'max:2000000'],
            'steering_position' => ['required', Rule::enum(SteeringPosition::class)],
            'doors' => ['nullable', 'integer', 'min:2', 'max:6'],
            'location' => ['nullable', 'string', 'max:120'],
            'received_at' => ['required', 'date'],
            'acquisition_source' => ['nullable', 'string', 'max:150'],
            'acquisition_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'acquisition_reference' => ['nullable', 'string', 'max:80'],
            'acquired_from' => ['nullable', 'string', 'max:150'],
            'advertised_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'minimum_price' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            'general_description' => ['nullable', 'string', 'max:3000'],
            'condition_remarks' => ['nullable', 'string', 'max:2000'],
            'mechanical_issues' => ['nullable', 'string', 'max:2000'],
            'bodywork_condition' => ['nullable', 'string', 'max:2000'],
            'accessories' => ['nullable', 'string', 'max:2000'],
            'internal_notes' => ['nullable', 'string', 'max:3000'],
            'is_public_listing' => ['sometimes', 'boolean'],
        ];
    }
}
