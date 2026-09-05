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
            sourceProductId: (string) $data['source_product_id'],
            categoryId: (int) $data['category_id'],
            unitPrice: (string) $data['unit_price'],
            totalQuantity: (int) $data['total_quantity'],
            status: OfferStatus::from($data['status'] ?? OfferStatus::INACTIVE->value),
        );
    }
}
