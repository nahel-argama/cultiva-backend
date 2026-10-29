<?php

namespace Cultiva\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadDocumentRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'document' => ['required', 'file', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
