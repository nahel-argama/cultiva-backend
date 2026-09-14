<?php

namespace Cultiva\Models\Wishlist\Actions;

use Cultiva\Models\Retailer\Retailer;
use Cultiva\Models\Wishlist\DTO\ListWishlistItemsDTO;
use Cultiva\Models\Wishlist\WishlistItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ListWishlistItemsAction
{
    public function execute(Retailer $retailer, ListWishlistItemsDTO $dto): LengthAwarePaginator
    {
        $search = $dto->search === null ? null : Str::lower(Str::ascii($dto->search));
        $productNameExpression = DB::getDriverName() === 'pgsql'
            ? 'unaccent(lower(product_name))'
            : 'lower(product_name)';

        return WishlistItem::query()
            ->where('retailer_id', $retailer->id)
            ->when($dto->search !== null, fn ($query) => $query->whereRaw(
                $productNameExpression.' LIKE ?',
                ['%'.$search.'%'],
            ))
            ->latest('created_at')
            ->latest('id')
            ->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }
}
