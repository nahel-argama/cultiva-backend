<?php

namespace Cultiva\Models\Address\Action;

use Cultiva\Models\Address\Address;
use Cultiva\Models\Address\DTOs\AddressRegisterDTO;

class CreateAddressAction
{

    public function execute(AddressRegisterDTO $dto): Address
    {
        return Address::query()->create([
            'addressable_type' => $dto->addressableType,
            'addressable_id'   => $dto->addressableId,
            'zip'              => $dto->zip,
            'street'           => $dto->street,
            'number'           => $dto->number,
            'complement'       => $dto->complement,
            'reference_point'  => $dto->referencePoint,
            'neighborhood'     => $dto->neighborhood,
            'city'             => $dto->city,
            'state'            => $dto->state,
            'coordinate'       => "POINT({$dto->longitude} {$dto->latitude})",
        ]);
    }
}
