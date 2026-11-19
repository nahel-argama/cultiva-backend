<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Offer\DTO\OfferFiltersDTO;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

final class ListAvailableOffersAction
{
    public function execute(User $user, int $page, int $perPage, OfferFiltersDTO $filters = new OfferFiltersDTO()): LengthAwarePaginator
    {
        $search = trim($filters->search ?? '');

        $user->loadMissing('company.address');
        $userCoordinate = $user->company->address->coordinate;

        $retailerId = $user->retailer()->firstOrFail()->id;

        $query = Offer::query()
            ->with('category')
            ->select('offers.*')
            ->join('producers', 'producers.id', '=', 'offers.producer_id')
            ->join('addresses as producer_addresses', 'producer_addresses.company_id', '=', 'producers.company_id')
            ->leftJoin('wishlist_items', function (JoinClause $join) use ($retailerId): void {
                $join->on('wishlist_items.source_product_id', '=', 'offers.source_product_id')
                    ->where('wishlist_items.retailer_id', $retailerId);
            })
            ->selectRaw(
                'ROUND((ST_Distance(producer_addresses.coordinate, ?::geography) / 1000.0)::numeric, 2)::float as distance_km',
                [$userCoordinate]
            )
            ->selectRaw('(wishlist_items.id IS NOT NULL) as is_in_wishlist')
            ->where('offers.status', OfferStatus::ACTIVE->value)
            ->whereColumn('offers.total_quantity', '>', 'offers.reserved_quantity')
            ->when($search !== '', fn(Builder $query) => $query->where('offers.product_name', 'ilike', '%' . $search . '%'))
            ->when($filters->wishlist === 'included', fn(Builder $query) => $query->whereNotNull('wishlist_items.id'))
            ->when($filters->wishlist === 'excluded', fn(Builder $query) => $query->whereNull('wishlist_items.id'))
            ->when($filters->maxDistanceKm !== null, fn(Builder $query) => $query->whereRaw(
                'ST_DWithin(producer_addresses.coordinate, ?::geography, ?)',
                [$userCoordinate, $filters->maxDistanceKm * 1000],
            ));

        match ($filters->sort) {
            'recent' => $query,
            'price_asc' => $query->orderBy('offers.unit_price'),
            'price_desc' => $query->orderByDesc('offers.unit_price'),
            default => $query->orderBy('distance_km'),
        };

        return $query->latest('offers.created_at')
            ->latest('offers.id')
            ->paginate(perPage: $perPage, page: $page);
    }
}
