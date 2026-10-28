<?php

namespace Cultiva\Models\Purchase\Actions;

use Cultiva\Models\Purchase\Purchase;
use Cultiva\Models\Retailer\Retailer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListRetailerPurchasesAction
{
    public function execute(Retailer $retailer, int $page, int $perPage, ?string $search = null): LengthAwarePaginator
    {
        $search = trim($search ?? '');

        return Purchase::query()
            ->with(['producer.company.user', 'producer.company.address', 'offer.category'])
            ->where('retailer_id', $retailer->id)
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
