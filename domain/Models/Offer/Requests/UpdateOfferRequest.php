<?php

namespace Cultiva\Models\Offer\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateOfferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'category_id' => ['required_without_all:unit_price,total_quantity', 'integer'],
            'unit_price' => ['string', 'numeric', 'gt:0', 'regex:/^[0-9]+(?:\.[0-9]{1,2})?$/'],
            'total_quantity' => ['integer', 'min:0'],
        ];
    }
}
