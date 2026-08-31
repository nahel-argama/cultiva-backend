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
        return [
            'trade_name' => $producer->trade_name,
            'legal_name' => $producer->legal_name,
            'is_company' => $producer->is_company,
            'document_number' => $producer->document_number,
            'phone' => $producer->phone,
            'address' => $this->addressTransformer->transform($producer->address),
        ];
    }
}
