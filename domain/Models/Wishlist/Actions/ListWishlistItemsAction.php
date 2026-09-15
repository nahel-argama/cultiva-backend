<?php

namespace Cultiva\Models\Wishlist\Actions;

use Cultiva\Models\Retailer\Retailer;
use Cultiva\Models\Wishlist\DTO\ListWishlistItemsDTO;
use Cultiva\Models\Wishlist\WishlistItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

final class ListWishlistItemsAction
{
    public function execute(Retailer $retailer, ListWishlistItemsDTO $dto): LengthAwarePaginator
    {
        $query = WishlistItem::query()->where('retailer_id', $retailer->id);

        if ($dto->search !== null) {
            $search = Str::lower(Str::ascii($dto->search));

            $query->whereRaw(
                'unaccent(lower(product_name)) LIKE ?',
                ["%{$search}%"],
            );
        }

        return $query->latest('created_at')
            ->latest('id')
            ->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }
}
