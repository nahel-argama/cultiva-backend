<?php

namespace Cultiva\Models\Retailer\DTOs;

use Cultiva\Models\Address\DTOs\AddressRegisterDTO;
use Cultiva\Models\Retailer\Enums\BusinessType;

final class RetailerRegisterDTO
{

    public function __construct(
        public readonly string             $tradeName,
        public readonly ?string            $legalName,
        public readonly string             $documentNumber,
        public readonly BusinessType       $businessType,
        public readonly string             $phone,
        public readonly AddressRegisterDTO $address,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            tradeName: $data['trade_name'],
            legalName: $data['legal_name'] ?? null,
            documentNumber: $data['document_number'],
            businessType: BusinessType::from($data['business_type']),
            phone: $data['phone'],
            address: AddressRegisterDTO::fromArray($data['address']),
        );
    }
}
