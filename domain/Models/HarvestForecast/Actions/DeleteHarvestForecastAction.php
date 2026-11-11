<?php

namespace Cultiva\Models\HarvestForecast\Actions;

use Cultiva\Models\Producer\Producer;

final class DeleteHarvestForecastAction
{
    public function execute(Producer $producer, int $forecastId): void
    {
        $producer->harvestForecasts()->findOrFail($forecastId)->delete();
    }
}
