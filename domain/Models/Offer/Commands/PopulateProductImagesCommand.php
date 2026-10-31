<?php

namespace Cultiva\Models\Offer\Commands;

use Cultiva\Models\Offer\Actions\PopulateProductImageAction;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Wishlist\WishlistItem;
use Illuminate\Console\Command;

class PopulateProductImagesCommand extends Command
{
    protected $signature = 'products:populate-images {--source-id= : Specific source product ID to populate}';

    protected $description = 'Populate product images into public storage in JPEG format';

    public function handle(PopulateProductImageAction $action): int
    {
        $sourceId = $this->option('source-id');

        if ($sourceId) {
            $this->info("Populating image for product: {$sourceId}");
            $path = $action->execute((string) $sourceId);
            $this->info("Populated image for product: {$sourceId} at {$path}");

            return self::SUCCESS;
        }

        $products = WishlistItem::query()
            ->select('source_product_id', 'product_name')
            ->distinct()
            ->union(
                Offer::query()->select('source_product_id', 'product_name')->distinct()
            )
            ->get();

        if ($products->isEmpty()) {
            $this->info('No products found to populate images for.');

            return self::SUCCESS;
        }

        $this->info("Found {$products->count()} unique products to populate images for.");

        foreach ($products as $product) {
            $this->line("Processing product: {$product->source_product_id} ({$product->product_name})...");
            $path = $action->execute($product->source_product_id, $product->product_name);
            $this->info("Populated image for product: {$product->source_product_id} at {$path}");
        }

        $this->info('Finished populating product images.');

        return self::SUCCESS;
    }
}
