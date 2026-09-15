<?php

namespace Cultiva\Models\Wishlist\DTO;

final readonly class WishlistAnalyticsResultDTO
{
    public function __construct(
        public int $position,
        public string $sourceProductId,
        public string $productName,
        public int $total,
        public int $percentage,
    ) {}
}
