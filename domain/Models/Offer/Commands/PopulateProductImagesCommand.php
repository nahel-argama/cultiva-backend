<?php

namespace Cultiva\Models\Offer\Commands;

use Cultiva\Models\Offer\Actions\PopulateProductImageAction;
use Cultiva\Models\Offer\Offer;
use Illuminate\Console\Command;

class PopulateProductImagesCommand extends Command
{
    protected $signature = 'products:populate-images {--source-id= : Specific source product ID to populate}';

    protected $description = 'Populate product images into public storage in WebP format';

    public function handle(PopulateProductImageAction $action): int
    {
        $sourceId = $this->option('source-id');

        if ($sourceId) {
            $this->info("Populating image for product: {$sourceId}");
            $path = $action->execute((string) $sourceId);
            $this->info("Populated image for product: {$sourceId} at {$path}");

            return self::SUCCESS;
        }

        $offers = Offer::query()
            ->select('source_product_id', 'product_name')
            ->distinct()
            ->get();

        if ($offers->isEmpty()) {
            $this->info('No offers found to populate images for.');

            return self::SUCCESS;
        }

        $this->info("Found {$offers->count()} unique products to populate images for.");

        foreach ($offers as $offer) {
            $this->line("Processing product: {$offer->source_product_id} ({$offer->product_name})...");
            $path = $action->execute($offer->source_product_id, $offer->product_name);
            $this->info("Populated image for product: {$offer->source_product_id} at {$path}");
        }

        $this->info('Finished populating product images.');

        return self::SUCCESS;
    }
}
