<?php

namespace Cultiva\Models\Purchase\Actions;

use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Purchase\Purchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListProducerSalesAction
{
    public function execute(Producer $producer, int $page, int $perPage): LengthAwarePaginator
    {
        return Purchase::query()
            ->where('producer_id', $producer->id)
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
