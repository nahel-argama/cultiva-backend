<?php

namespace Cultiva\Models\Offer\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CompareOffersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'first_offer_id' => ['required', 'integer', 'min:1'],
            'second_offer_id' => ['required', 'integer', 'min:1', 'different:first_offer_id'],
        ];
    }
}
