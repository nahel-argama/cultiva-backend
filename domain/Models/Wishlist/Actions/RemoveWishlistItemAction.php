<?php

namespace Cultiva\Models\Wishlist\Actions;

use Cultiva\Models\Retailer\Retailer;

final class RemoveWishlistItemAction
{
    public function execute(Retailer $retailer, int $wishlistItemId): void
    {
        $retailer->wishlistItems()->findOrFail($wishlistItemId)->delete();
    }
}
