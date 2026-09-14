<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Offer\Offer;

class GetAvailableOfferAction
{
    public function execute(int $offerId): Offer
    {
        return Offer::query()
            ->with('category')
            ->whereKey($offerId)
            ->where('status', OfferStatus::ACTIVE->value)
            ->whereColumn('total_quantity', '>', 'reserved_quantity')
            ->firstOrFail();
    }
}
