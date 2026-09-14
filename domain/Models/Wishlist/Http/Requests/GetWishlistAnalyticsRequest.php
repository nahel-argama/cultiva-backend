<?php

namespace Cultiva\Models\Wishlist\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class GetWishlistAnalyticsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'state' => ['nullable', 'string', 'size:2'],
            'limit' => ['integer', 'between:1,50'],
        ];
    }
}
