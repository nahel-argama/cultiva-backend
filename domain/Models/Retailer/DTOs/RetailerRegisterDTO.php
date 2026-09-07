<?php

namespace Cultiva\Models\Retailer\DTOs;

use Cultiva\Models\Retailer\Enums\BusinessType;

final class RetailerRegisterDTO
{
    public function __construct(
        public readonly BusinessType $businessType,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            businessType: BusinessType::from($data['business_type']),
        );
    }
}
