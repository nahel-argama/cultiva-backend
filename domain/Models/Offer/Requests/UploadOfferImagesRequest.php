<?php

namespace Cultiva\Models\Offer\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UploadOfferImagesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:3'],
            'images.*' => ['required', 'image', 'max:5120'],
        ];
    }
}
