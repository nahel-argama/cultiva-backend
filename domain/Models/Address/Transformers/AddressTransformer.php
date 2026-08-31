<?php

namespace Cultiva\Models\Address\Transformers;

use Cultiva\Models\Address\Address;

final class AddressTransformer
{
    public function transform(Address $address): array
    {
        return [
            'zip' => $address->zip,
            'street' => $address->street,
            'number' => $address->number,
            'complement' => $address->complement,
            'reference_point' => $address->reference_point,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
        ];
    }
}
