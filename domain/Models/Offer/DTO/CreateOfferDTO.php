<?php

namespace Cultiva\Models\Offer\DTO;

use Cultiva\Models\Offer\Enums\OfferStatus;

final readonly class CreateOfferDTO
{
    public function __construct(
        public string $sourceProductId,
        public int $categoryId,
        public string $unitPrice,
        public int $totalQuantity,
        public OfferStatus $status,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            sourceProductId: $data['source_product_id'],
            categoryId: $data['category_id'],
            unitPrice: $data['unit_price'],
            totalQuantity: $data['total_quantity'],
            status: OfferStatus::from($data['status'] ?? OfferStatus::INACTIVE->value),
        );
    }
}
