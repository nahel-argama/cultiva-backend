<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListAvailableOffersAction
{
    public function execute(User $user, int $page, int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $search = trim($search ?? '');

        $user->loadMissing('company.address');
        $userCoordinate = $user->company?->address?->coordinate;

        $query = Offer::query()
            ->with('category')
            ->select('offers.*')
            ->where('offers.status', OfferStatus::ACTIVE->value)
            ->whereColumn('offers.total_quantity', '>', 'offers.reserved_quantity')
            ->when($search !== '', fn ($query) => $query->where('offers.product_name', 'ilike', '%'.$search.'%'));

        if ($userCoordinate !== null) {
            $query->leftJoin('producers', 'producers.id', '=', 'offers.producer_id')
                ->leftJoin('addresses as producer_addresses', 'producer_addresses.company_id', '=', 'producers.company_id')
                ->selectRaw(
                    'ROUND((ST_Distance(producer_addresses.coordinate, ?::geography) / 1000.0)::numeric, 2)::float as distance_km',
                    [$userCoordinate]
                )
                ->orderByRaw('distance_km ASC NULLS LAST');
        } else {
            $query->selectRaw('NULL::float as distance_km');
        }

        return $query
            ->latest('offers.created_at')
            ->latest('offers.id')
            ->paginate(perPage: $perPage, page: $page);
    }
}
