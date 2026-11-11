<?php

namespace Cultiva\Models\HarvestForecast\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreHarvestForecastRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'source_product_id' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'start_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }
}
