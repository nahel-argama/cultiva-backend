<?php

namespace Cultiva\Models\Producer\DTOs;

use Cultiva\Models\Address\DTOs\AddressRegisterDTO;

final class ProducerRegisterDTO
{

    public function __construct(
        public readonly string             $tradeName,
        public readonly ?string            $legalName,
        public readonly bool               $isCompany,
        public readonly string             $documentNumber,
        public readonly string             $phone,
        public readonly AddressRegisterDTO $address,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tradeName: $data['trade_name'],
            legalName: $data['legal_name'] ?? null,
            isCompany: $data['is_company'],
            documentNumber: $data['document_number'],
            phone: $data['phone'],
            address: AddressRegisterDTO::fromArray($data['address']),
        );
    }
}
