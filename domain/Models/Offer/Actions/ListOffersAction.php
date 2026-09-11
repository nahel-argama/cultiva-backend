<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListOffersAction
{
    public function __construct(
        private readonly ListProducerOffersAction $producerOffers,
        private readonly ListAvailableOffersAction $availableOffers,
    ) {}

    public function execute(User $user, int $page, int $perPage): LengthAwarePaginator
    {
        return match ($user->profile_type) {
            ProfileType::PRODUCER => $this->producerOffers->execute(
                $user->producer()->firstOrFail(),
                $page,
                $perPage,
            ),
            ProfileType::RETAILER => $this->availableOffers->execute($page, $perPage),
        };
    }
}
