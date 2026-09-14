<?php

namespace Cultiva\Models\Wishlist\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AddWishlistItemRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'max:255'],
        ];
    }
}
