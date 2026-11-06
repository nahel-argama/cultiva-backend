<?php

namespace Cultiva\Integrations\GoLog\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateGoLogUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'user.name' => ['required', 'string', 'max:100'],
            'user.email' => ['required', 'string', 'email', 'max:100'],
            'user.password' => ['required', 'string', 'min:8', 'confirmed'],
            'user.cpf' => ['required', 'string', 'digits:11'],
            'company.trade_name' => ['required', 'string', 'max:100'],
            'company.legal_name' => ['nullable', 'string', 'max:100'],
            'company.document_number' => ['required', 'string', 'digits:14'],
            'company.phone' => ['required', 'string', 'digits:11'],
            'company.address.zip' => ['required', 'string', 'digits:8'],
            'company.address.number' => ['required', 'string', 'max:20'],
            'company.address.complement' => ['nullable', 'string', 'max:50'],
            'company.address.reference_point' => ['nullable', 'string', 'max:150'],
        ];
    }
}
