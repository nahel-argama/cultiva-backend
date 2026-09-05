<?php

namespace Cultiva\Models\Offer\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListOffersRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'between:1,100'],
        ];
    }
}
