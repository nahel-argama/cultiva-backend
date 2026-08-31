<?php

namespace Cultiva\Models\Retailer\Transformers;

use Cultiva\Models\Address\Transformers\AddressTransformer;
use Cultiva\Models\Retailer\Retailer;

final class RetailerTransformer
{
    public function __construct(
        private readonly AddressTransformer $addressTransformer,
    ) {}

    public function transform(Retailer $retailer): array
    {
        return [
            'trade_name' => $retailer->trade_name,
            'legal_name' => $retailer->legal_name,
            'document_number' => $retailer->document_number,
            'business_type' => $retailer->business_type->value,
            'phone' => $retailer->phone,
            'address' => $this->addressTransformer->transform($retailer->address),
        ];
    }
}
