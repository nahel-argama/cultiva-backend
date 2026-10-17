<?php

namespace Cultiva\Models\DeliveryTrip\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListFinishedTripsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'between:1,100'],
        ];
    }
}
