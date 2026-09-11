<?php

namespace Cultiva\Models\Purchase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StorePurchaseRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
