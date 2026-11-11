<?php

namespace Cultiva\Models\HarvestForecast\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateHarvestForecastRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'source_product_id' => ['required_without_all:quantity,start_date,end_date', 'string', 'max:255'],
            'quantity' => ['required_without_all:source_product_id,start_date,end_date', 'integer', 'min:1'],
            'start_date' => ['required_without_all:source_product_id,quantity,end_date', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'end_date' => ['required_without_all:source_product_id,quantity,start_date', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
        ];
    }
}
