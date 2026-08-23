<?php

namespace Cultiva\Models\Producer\Actions;

use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Models\Address\Action\CreateAddressAction;
use Cultiva\Models\Producer\DTOs\ProducerRegisterDTO;
use Cultiva\Models\Producer\Producer;
use Illuminate\Support\Facades\DB;

class CreateProducerAction
{

    public function __construct(
        private readonly CreateAddressAction $createAddress,
    ) {}

    public function execute(int $userId, ProducerRegisterDTO $dto, GeoAddressDTO $geo): Producer
    {
        return DB::transaction(function () use ($dto, $userId, $geo) {
            $producer = Producer::query()->create([
                'user_id'         => $userId,
                'is_company'      => $dto->isCompany,
                'document_number' => $dto->documentNumber,
                'trade_name'      => $dto->tradeName,
                'legal_name'      => $dto->legalName,
                'phone'           => $dto->phone,
            ]);

            $address = $this->createAddress->execute($producer, $dto->address, $geo);

            $producer->setRelation('address', $address);

            return $producer;
        });
    }
}
