<?php

namespace Cultiva\Models\Wishlist\DTO;

final readonly class WishlistAnalyticsOthersDTO
{
    public function __construct(
        public int $total,
        public int $percentage,
    ) {}
}
