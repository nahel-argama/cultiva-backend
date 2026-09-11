<?php

namespace Cultiva\Models\Offer\Actions;

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
}
