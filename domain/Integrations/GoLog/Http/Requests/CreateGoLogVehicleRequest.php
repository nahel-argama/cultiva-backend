<?php

namespace Cultiva\Integrations\GoLog\Http\Requests;

use Cultiva\Integrations\GoLog\Enums\GoLogFuelType;
use Cultiva\Integrations\GoLog\Enums\GoLogVehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateGoLogVehicleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'plate' => ['required', 'string', 'min:7', 'max:8'],
            'capacity' => ['required', 'numeric', 'gt:0'],
            'renavam' => ['required', 'string', 'digits:11'],
            'model' => ['required', 'string', 'max:100'],
            'number_axles' => ['required', 'integer', 'min:1'],
            'type_fuel' => ['required', Rule::enum(GoLogFuelType::class)],
            'cost_per_kilometer' => ['required', 'numeric', 'gt:0'],
            'vehicle_type' => ['nullable', Rule::enum(GoLogVehicleType::class)],
            'cargo_volume' => ['nullable', 'numeric', 'gt:0'],
            'km_per_liter' => ['nullable', 'numeric', 'gt:0'],
        ];
    }
}
