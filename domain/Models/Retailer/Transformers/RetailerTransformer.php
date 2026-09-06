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
        $company = $retailer->company;

        return [
            'trade_name'      => $company->trade_name,
            'legal_name'      => $company->legal_name,
            'document_number' => $company->document_number,
            'business_type'   => $retailer->business_type->value,
            'phone'           => $company->phone,
            'address'         => $this->addressTransformer->transform($company->address),
        ];
    }
}
