<?php

namespace Cultiva\Models\Wishlist\DTO;

final readonly class AddWishlistItemDTO
{
    public function __construct(public string $productId) {}

    public static function from(array $data): self
    {
        return new self(productId: $data['product_id']);
    }
}
