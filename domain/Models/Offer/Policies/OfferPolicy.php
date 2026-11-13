<?php

namespace Cultiva\Models\Offer\Policies;

use Cultiva\Models\Offer\Offer;
use Cultiva\Models\User\User;

final class OfferPolicy
{
    public function update(User $user, Offer $offer): bool
    {
        return $user->producer?->id === $offer->producer_id;
    }

    public function deleteImage(User $user, Offer $offer): bool
    {
        return $user->producer?->id === $offer->producer_id;
    }
}
