<?php

namespace Cultiva\Models\Wishlist\DTO;

final readonly class ListWishlistItemsDTO
{
    public function __construct(
        public ?string $search,
        public int $page,
        public int $perPage,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            search: $data['search'] ?? null,
            page: $data['page'] ?? 1,
            perPage: $data['per_page'] ?? 15,
        );
    }
}
