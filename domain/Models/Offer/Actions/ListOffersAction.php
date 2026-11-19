<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Offer\DTO\OfferFiltersDTO;
use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\User\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListOffersAction
{
    public function __construct(
        private readonly ListProducerOffersAction $producerOffers,
        private readonly ListAvailableOffersAction $availableOffers,
    ) {}

    public function execute(User $user, int $page, int $perPage, OfferFiltersDTO $filters = new OfferFiltersDTO()): LengthAwarePaginator
    {
        return match ($user->profile_type) {
            ProfileType::PRODUCER => $this->producerOffers->execute(
                $user->producer()->firstOrFail(),
                $page,
                $perPage,
                $filters->search,
            ),
            ProfileType::RETAILER => $this->availableOffers->execute(
                $user,
                $page,
                $perPage,
                $filters,
            ),
        };
    }
}
