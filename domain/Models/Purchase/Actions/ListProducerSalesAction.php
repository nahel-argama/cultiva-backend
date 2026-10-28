<?php

namespace Cultiva\Models\Purchase\Actions;

use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Purchase\Purchase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListProducerSalesAction
{
    public function execute(Producer $producer, int $page, int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $search = trim($search ?? '');

        return Purchase::query()
            ->with(['retailer.company.user', 'retailer.company.address', 'offer.category'])
            ->where('producer_id', $producer->id)
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('product_name', 'ilike', '%'.$search.'%');

                if (ctype_digit($search)) {
                    $query->orWhereRaw('CAST(id AS TEXT) = ?', [$search]);
                }
            }))
            ->latest('created_at')
            ->latest('id')
            ->paginate(perPage: $perPage, page: $page);
    }
}
