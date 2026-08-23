<?php

namespace Cultiva\Models\Address\Action;

use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Models\Address\Address;
use Cultiva\Models\Address\DTOs\AddressRegisterDTO;
use Illuminate\Database\Eloquent\Model;

class CreateAddressAction
{

    public function execute(Model $addressable, AddressRegisterDTO $dto, GeoAddressDTO $geo): Address
    {
        return Address::query()->create([
            'addressable_type' => $addressable::class,
            'addressable_id'   => $addressable->getKey(),
            'zip'              => $dto->zip,
            'street'           => $geo->street,
            'number'           => $dto->number,
            'complement'       => $dto->complement,
            'reference_point'  => $dto->referencePoint,
            'neighborhood'     => $geo->neighborhood,
            'city'             => $geo->city,
            'state'            => $geo->state,
            'coordinate'       => "POINT({$geo->longitude} {$geo->latitude})",
        ]);
    }
}
