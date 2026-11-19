<?php

namespace Cultiva\Models\Offer\DTO;

final readonly class OfferFiltersDTO
{
    public function __construct(
        public ?string $search = null,
        public string $wishlist = 'all',
        public ?int $maxDistanceKm = null,
        public string $sort = 'nearest',
    ) {}

    public static function from(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            wishlist: $data['wishlist'] ?? 'all',
            maxDistanceKm: $data['max_distance_km'] ?? null,
            sort: $data['sort'] ?? 'nearest',
        );
    }
}
