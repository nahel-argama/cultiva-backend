<?php

namespace Cultiva\Models\HarvestForecast\Actions;

use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Cultiva\Models\HarvestForecast\DTO\CreateHarvestForecastDTO;
use Cultiva\Models\HarvestForecast\HarvestForecast;
use Cultiva\Models\Producer\Producer;

final class CreateHarvestForecastAction
{
    public function __construct(
        private readonly GetProductAction $getProduct,
    ) {}

    public function execute(Producer $producer, CreateHarvestForecastDTO $data): HarvestForecast
    {
        $product = $this->getProduct->execute($data->sourceProductId);

        return $producer->harvestForecasts()->create([
            'source_product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => $data->quantity,
            'start_date' => $data->startDate,
            'end_date' => $data->endDate,
        ]);
    }
}
