<?php

namespace Cultiva\Models\HarvestForecast\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListHarvestSeasonPeriodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_product_id' => ['required', 'string', 'max:255'],
        ];
    }
}
