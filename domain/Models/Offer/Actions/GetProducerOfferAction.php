<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Producer\Producer;

class GetProducerOfferAction
{
    public function execute(Producer $producer, int $offerId): Offer
    {
        return $producer->offers()
            ->with('category')
            ->findOrFail($offerId);
    }
}
