<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Producer\Producer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListProducerOffersAction
{
    public function execute(Producer $producer, int $page, int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $search = trim($search ?? '');

        return $producer->offers()
            ->with('category')
            ->when($search !== '', fn ($query) => $query->where('product_name', 'ilike', '%'.$search.'%'))
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
