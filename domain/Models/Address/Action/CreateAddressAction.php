<?php

namespace Cultiva\Models\Address\Action;

use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Models\Address\Address;
use Cultiva\Models\Address\DTOs\AddressRegisterDTO;
use Cultiva\Models\Company\Company;

class CreateAddressAction
{
    public function execute(Company $company, AddressRegisterDTO $dto, GeoAddressDTO $geo): Address
    {
        return Address::query()->create([
            'company_id'      => $company->id,
            'zip'             => $dto->zip,
            'street'          => $geo->street,
            'number'          => $dto->number,
            'complement'      => $dto->complement,
            'reference_point' => $dto->referencePoint,
            'neighborhood'    => $geo->neighborhood,
            'city'            => $geo->city,
            'state'           => $geo->state,
            'coordinate'      => "POINT({$geo->longitude} {$geo->latitude})",
        ]);
    }
}
