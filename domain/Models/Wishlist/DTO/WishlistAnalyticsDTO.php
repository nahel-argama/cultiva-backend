<?php

namespace Cultiva\Models\Wishlist\DTO;

final readonly class WishlistAnalyticsDTO
{
    /** @param list<WishlistAnalyticsResultDTO> $results */
    public function __construct(
        public ?string $state,
        public int $totalItems,
        public array $results,
        public ?WishlistAnalyticsOthersDTO $others = null,
    ) {}
}
