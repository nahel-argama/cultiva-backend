<?php

namespace Cultiva\Models\Offer\DTO;

final readonly class UpdateOfferDTO
{
    public function __construct(
        public ?int $categoryId,
        public ?string $unitPrice,
        public ?int $totalQuantity,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            categoryId: $data['category_id'] ?? null,
            unitPrice: $data['unit_price'] ?? null,
            totalQuantity: $data['total_quantity'] ?? null,
        );
    }
}
