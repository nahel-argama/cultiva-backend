<?php

namespace Cultiva\Integrations\GoLog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGoLogEquipamentGroupRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'observation' => ['nullable', 'string', 'max:255'],
        ];
    }
}
