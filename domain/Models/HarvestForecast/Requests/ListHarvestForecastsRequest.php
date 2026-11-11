<?php

namespace Cultiva\Models\HarvestForecast\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListHarvestForecastsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'source_product_id' => ['string', 'max:255'],
            'date_from' => ['required_with:date_to', 'date', 'date_format:Y-m-d'],
            'date_to' => ['required_with:date_from', 'date', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'min:1', 'max:100'],
        ];
    }
}
