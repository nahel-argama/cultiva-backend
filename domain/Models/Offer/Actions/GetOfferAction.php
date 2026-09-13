<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\User\User;

final class GetOfferAction
{
    public function __construct(
        private readonly GetProducerOfferAction $producerOffer,
        private readonly GetAvailableOfferAction $availableOffer,
    ) {}

    public function execute(User $user, int $offerId): Offer
    {
        return match ($user->profile_type) {
            ProfileType::PRODUCER => $this->producerOffer->execute(
                $user->producer()->firstOrFail(),
                $offerId,
            ),
            ProfileType::RETAILER => $this->availableOffer->execute($offerId),
        };
    }
}
