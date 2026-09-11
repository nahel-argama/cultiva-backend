<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Producer\Producer;

final class GetOfferAction
{
    public function execute(Producer $producer, int $offerId): Offer
    {
        $offer = $producer->offers()
            ->with('category')
            ->findOrFail($offerId);

        return $offer;
    }

    public function executeForRetailer(int $offerId): Offer
    {
        $offer = Offer::query()
            ->with('category')
            ->whereKey($offerId)
            ->where('status', OfferStatus::ACTIVE->value)
            ->whereColumn('total_quantity', '>', 'reserved_quantity')
            ->firstOrFail();

        return $offer;
    }
}
