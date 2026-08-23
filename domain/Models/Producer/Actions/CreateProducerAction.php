<?php

namespace Cultiva\Models\Producer\Actions;

use Cultiva\Models\Address\Action\CreateAddressAction;
use Cultiva\Models\Producer\DTOs\ProducerRegisterDTO;
use Cultiva\Models\Producer\Producer;
use Illuminate\Support\Facades\DB;

final class CreateProducerAction
{

    public function __construct(
        private readonly CreateAddressAction $createAddress,
    ) {}

    public function execute(int $userId, ProducerRegisterDTO $dto): Producer
    {
        return DB::transaction(function () use ($dto, $userId) {
            $address = $this->createAddress->execute($dto->address);

            $producer = Producer::query()->create([
                'user_id'         => $userId,
                'is_company'      => $dto->isCompany,
                'document_number' => $dto->documentNumber,
                'trade_name'      => $dto->tradeName,
                'legal_name'      => $dto->legalName,
                'phone'           => $dto->phone,
            ]);

            $producer->setRelation('address', $address);

            return $producer;
        });
    }
}
