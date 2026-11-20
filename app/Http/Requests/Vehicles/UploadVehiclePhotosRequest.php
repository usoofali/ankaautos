<?php

namespace App\Http\Requests\Vehicles;

use App\Enums\PhotoCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadVehiclePhotosRequest extends FormRequest
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
        return [
            'photos' => ['required', 'array', 'min:1', 'max:10'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'category' => ['required', Rule::enum(PhotoCategory::class)],
            'caption' => ['nullable', 'string', 'max:200'],
        ];
    }
}
