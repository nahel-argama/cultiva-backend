<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Producer\Producer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListProducerOffersAction
{
    public function execute(Producer $producer, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        return $producer->offers()
            ->with('category')
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
