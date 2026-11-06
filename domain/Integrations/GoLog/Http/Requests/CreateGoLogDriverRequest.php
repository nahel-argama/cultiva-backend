<?php

namespace Cultiva\Integrations\GoLog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGoLogDriverRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'cnh_number' => ['required', 'string', 'digits:11'],
            'cnh_expiration' => ['required', 'date', 'date_format:Y-m-d'],
            'cost_per_hour' => ['required', 'numeric', 'gt:0'],
        ];
    }
}
