<?php

namespace Cultiva\Models\HarvestForecast\Actions;

use Cultiva\Models\HarvestForecast\DTO\ListHarvestForecastsDTO;
use Cultiva\Models\HarvestForecast\HarvestForecast;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListHarvestForecastsAction
{
    public function execute(ListHarvestForecastsDTO $data): LengthAwarePaginator
    {
        return HarvestForecast::query()
            ->with('producer.company.address')
            ->whereDate('end_date', '>=', today())
            ->when($data->producerId !== null, fn ($query) => $query->where('producer_id', $data->producerId))
            ->when($data->sourceProductId, fn ($query) => $query->where('source_product_id', $data->sourceProductId))
            ->when($data->dateFrom, function ($query) use ($data): void {
                $query->whereDate('start_date', '<=', $data->dateTo)
                    ->whereDate('end_date', '>=', $data->dateFrom);
            })
            ->orderBy('start_date')
            ->orderBy('id')
            ->paginate($data->perPage, ['*'], 'page', $data->page);
    }
}
