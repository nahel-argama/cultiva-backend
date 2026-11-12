<?php

namespace Cultiva\Models\Purchase\DTO;

readonly class CreatePurchaseDTO
{
    public function __construct(
        public int $quantity,
        public ?float $expectedUnitPrice = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            quantity: $data['quantity'],
            expectedUnitPrice: isset($data['expected_unit_price']) ? (float) $data['expected_unit_price'] : null,
        );
    }
}
