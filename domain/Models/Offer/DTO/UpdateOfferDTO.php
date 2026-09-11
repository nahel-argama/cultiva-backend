<?php

namespace Cultiva\Models\Offer\DTO;

use Cultiva\Models\Offer\Enums\OfferStatus;

final readonly class UpdateOfferDTO
{
    public function __construct(
        public ?string $sourceProductId,
        public ?int $categoryId,
        public ?string $unitPrice,
        public ?int $totalQuantity,
        public ?OfferStatus $status,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            sourceProductId: $data['source_product_id'] ?? null,
            categoryId: $data['category_id'] ?? null,
            unitPrice: $data['unit_price'] ?? null,
            totalQuantity: $data['total_quantity'] ?? null,
            status: ($data['status'] ?? null) === null ? null : OfferStatus::from($data['status']),
        );
    }
}
