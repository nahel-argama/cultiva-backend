<?php

namespace Cultiva\Models\Wishlist\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\User\User;
use Cultiva\Models\Wishlist\DTO\GetWishlistAnalyticsDTO;
use Cultiva\Models\Wishlist\DTO\WishlistAnalyticsDTO;
use Cultiva\Models\Wishlist\DTO\WishlistAnalyticsOthersDTO;
use Cultiva\Models\Wishlist\DTO\WishlistAnalyticsResultDTO;
use Cultiva\Models\Wishlist\WishlistItem;
use Illuminate\Support\Facades\Lang;

final class GetWishlistAnalyticsAction
{
    public function execute(User $user, GetWishlistAnalyticsDTO $dto): WishlistAnalyticsDTO
    {
        if (! $user->isRetailer() && ! $user->isProducer()) {
            throw new CultivaException(403, Lang::get('auth.forbidden'));
        }

        $query = WishlistItem::query()->when(
            $dto->state !== null,
            fn ($query) => $query->where('state', $dto->state),
        );
        $totalItems = (clone $query)->count();
        $globalTotalItems = WishlistItem::query()->count();
        $groups = $query
            ->selectRaw('source_product_id, product_name, COUNT(*) as total')
            ->groupBy('source_product_id', 'product_name')
            ->orderByDesc('total')
            ->orderBy('source_product_id')
            ->limit($dto->limit + 1)
            ->get();

        $results = [];
        foreach ($groups->take($dto->limit) as $index => $group) {
            $results[] = new WishlistAnalyticsResultDTO(
                position: $index + 1,
                sourceProductId: $group->source_product_id,
                productName: $group->product_name,
                total: (int) $group->total,
                percentage: (int) round($group->total * 100 / $globalTotalItems),
            );
        }

        $others = null;
        if ($groups->count() > $dto->limit) {
            $topTotal = $groups->take($dto->limit)->sum('total');
            $othersTotal = $totalItems - $topTotal;
            $others = new WishlistAnalyticsOthersDTO(
                total: $othersTotal,
                percentage: (int) round($othersTotal * 100 / $globalTotalItems),
            );
        }

        return new WishlistAnalyticsDTO(
            state: $dto->state,
            totalItems: $totalItems,
            results: $results,
            others: $others,
        );
    }
}
