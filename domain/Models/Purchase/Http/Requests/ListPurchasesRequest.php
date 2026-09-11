<?php

namespace Cultiva\Models\Purchase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListPurchasesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page' => ['integer', 'min:1'],
            'per_page' => ['integer', 'between:1,100'],
        ];
    }
}
