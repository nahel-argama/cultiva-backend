<?php

namespace Cultiva\Models\Purchase\Actions;

use Cultiva\Models\Purchase\Purchase;
use Cultiva\Models\Retailer\Retailer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListRetailerPurchasesAction
{
    public function execute(Retailer $retailer, int $page, int $perPage): LengthAwarePaginator
    {
        return Purchase::query()
            ->where('retailer_id', $retailer->id)
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
