<?php

namespace Cultiva\Models\Wishlist\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListWishlistItemsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'between:1,100'],
        ];
    }
}
