<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Offer\Offer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListAvailableOffersAction
{
    public function execute(int $page, int $perPage): LengthAwarePaginator
    {
        return Offer::query()
            ->with('category')
            ->where('status', OfferStatus::ACTIVE->value)
            ->whereColumn('total_quantity', '>', 'reserved_quantity')
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
