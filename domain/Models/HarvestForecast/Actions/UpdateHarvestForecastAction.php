<?php

namespace Cultiva\Models\HarvestForecast\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Cultiva\Models\HarvestForecast\DTO\UpdateHarvestForecastDTO;
use Cultiva\Models\HarvestForecast\HarvestForecast;
use Cultiva\Models\Producer\Producer;
use Illuminate\Support\Facades\Lang;

final class UpdateHarvestForecastAction
{
    public function __construct(
        private readonly GetProductAction $getProduct,
    ) {}

    public function execute(Producer $producer, int $forecastId, UpdateHarvestForecastDTO $data): HarvestForecast
    {
        $forecast = $producer->harvestForecasts()->findOrFail($forecastId);
        $startDate = $data->startDate ?? $forecast->start_date->toDateString();
        $endDate = $data->endDate ?? $forecast->end_date->toDateString();

        if (($data->startDate !== null && $startDate < today()->toDateString())
            || $endDate < today()->toDateString()
            || $startDate > $endDate) {
            throw new CultivaException(422, Lang::get('harvest_forecasts.invalid_date_range'));
        }

        $attributes = [
            'quantity' => $data->quantity ?? $forecast->quantity,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        if ($data->sourceProductId !== null) {
            $product = $this->getProduct->execute($data->sourceProductId);
            $attributes['source_product_id'] = $product->id;
            $attributes['product_name'] = $product->name;
        }

        $forecast->update($attributes);

        return $forecast->load('producer.company.address');
    }
}
