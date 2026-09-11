<?php

namespace Cultiva\Models\Purchase\DTO;

readonly class CreatePurchaseDTO
{
    public function __construct(
        public int $quantity,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            quantity: $data['quantity'],
        );
    }
}
