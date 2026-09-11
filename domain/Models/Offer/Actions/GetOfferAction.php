<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Producer\Producer;
use Illuminate\Support\Facades\Lang;

final class GetOfferAction
{
    public function execute(Producer $producer, int $offerId): Offer
    {
        $offer = $producer->offers()
            ->with('category')
            ->find($offerId);

        if (! $offer instanceof Offer) {
            throw new CultivaException(404, Lang::get('offers.not_found'));
        }

        return $offer;
    }
}
