<?php

namespace Cultiva\Models\Retailer\Actions;

use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Models\Address\Action\CreateAddressAction;
use Cultiva\Models\Retailer\DTOs\RetailerRegisterDTO;
use Cultiva\Models\Retailer\Retailer;
use Illuminate\Support\Facades\DB;

class CreateRetailerAction
{

    public function __construct(
        private readonly CreateAddressAction $createAddress,
    ) {}

    public function execute(int $userId, RetailerRegisterDTO $dto, GeoAddressDTO $geo): Retailer
    {
        return DB::transaction(function () use ($dto, $userId, $geo) {
            $retailer = Retailer::query()->create([
                'user_id'         => $userId,
                'document_number' => $dto->documentNumber,
                'trade_name'      => $dto->tradeName,
                'legal_name'      => $dto->legalName,
                'business_type'   => $dto->businessType->value,
                'phone'           => $dto->phone,
            ]);

            $address = $this->createAddress->execute($retailer, $dto->address, $geo);

            $retailer->setRelation('address', $address);

            return $retailer;
        });
    }
}
