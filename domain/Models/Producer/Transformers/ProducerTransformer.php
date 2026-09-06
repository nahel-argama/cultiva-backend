<?php

namespace Cultiva\Models\Producer\Transformers;

use Cultiva\Models\Address\Transformers\AddressTransformer;
use Cultiva\Models\Producer\Producer;

final class ProducerTransformer
{
    public function __construct(
        private readonly AddressTransformer $addressTransformer,
    ) {}

    public function transform(Producer $producer): array
    {
        $company = $producer->company;

        return [
            'trade_name'       => $company->trade_name,
            'legal_name'       => $company->legal_name,
            'document_number'  => $company->document_number,
            'activity_segment' => $producer->activity_segment->value,
            'phone'            => $company->phone,
            'address'          => $this->addressTransformer->transform($company->address),
        ];
    }
}
