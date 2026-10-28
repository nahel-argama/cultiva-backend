<?php

namespace Cultiva\Models\Purchase\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListPurchasesRequest extends FormRequest
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
