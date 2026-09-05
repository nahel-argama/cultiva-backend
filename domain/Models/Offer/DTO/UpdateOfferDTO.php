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
            sourceProductId: array_key_exists('source_product_id', $data)
                ? (string) $data['source_product_id']
                : null,
            categoryId: array_key_exists('category_id', $data) ? (int) $data['category_id'] : null,
            unitPrice: array_key_exists('unit_price', $data) ? (string) $data['unit_price'] : null,
            totalQuantity: array_key_exists('total_quantity', $data) ? (int) $data['total_quantity'] : null,
            status: array_key_exists('status', $data) ? OfferStatus::from($data['status']) : null,
        );
    }
}
