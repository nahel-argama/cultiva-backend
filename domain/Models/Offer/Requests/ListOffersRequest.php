<?php

namespace Cultiva\Models\Offer\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListOffersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'source_product_id' => ['nullable', 'string', 'max:255'],
            'search' => ['nullable', 'string', 'max:255'],
            'wishlist' => ['in:all,included,excluded'],
            'max_distance_km' => ['integer', 'between:1,20000'],
            'sort' => ['in:nearest,recent,price_asc,price_desc'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
