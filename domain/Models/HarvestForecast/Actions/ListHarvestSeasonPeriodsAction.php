<?php

namespace Cultiva\Models\HarvestForecast\Actions;

use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Illuminate\Support\Collection;

final class ListHarvestSeasonPeriodsAction
{
    public function __construct(
        private readonly GetProductAction $getProduct,
    ) {}

    public function execute(string $sourceProductId): Collection
    {
        $product = $this->getProduct->execute($sourceProductId);
        $periods = config('harvest_seasons', [])[$product->name] ?? [];

        return collect($periods)->map(fn (array $period): array => [
            'source_product_id' => $product->id,
            'product_name' => $product->name,
            ...$period,
        ]);
    }
}
