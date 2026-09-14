<?php

namespace Cultiva\Models\Wishlist\DTO;

final readonly class GetWishlistAnalyticsDTO
{
    public function __construct(
        public ?string $state,
        public int $limit,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            state: $data['state'] ?? null,
            limit: $data['limit'] ?? 10,
        );
    }
}
