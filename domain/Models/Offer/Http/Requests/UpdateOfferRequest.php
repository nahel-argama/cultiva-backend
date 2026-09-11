<?php

namespace Cultiva\Models\Offer\Http\Requests;

use Cultiva\Models\Offer\Enums\OfferStatus;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOfferRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_int($this->input('source_product_id'))) {
            $this->merge(['source_product_id' => (string) $this->input('source_product_id')]);
        }
    }

    public function rules(): array
    {
        return [
            'source_product_id' => [
                'required_without_all:category_id,unit_price,total_quantity',
                'string',
                'max:255',
            ],
            'category_id' => ['sometimes', 'integer'],
            'unit_price' => ['sometimes', 'string', 'numeric', 'gt:0', 'regex:/^[0-9]+(?:\.[0-9]{1,2})?$/'],
            'total_quantity' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::enum(OfferStatus::class)],
        ];
    }
}
