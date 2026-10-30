<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Integrations\ProductImage\Clients\ProductImageClient;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PopulateProductImageAction
{
    public function __construct(
        private readonly ProductImageClient $client,
        private readonly GetProductAction $getProduct,
    ) {}

    public function execute(string $sourceProductId, ?string $productName = null): string
    {
        $path = "products/{$sourceProductId}.webp";

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        try {
            if ($productName === null) {
                $product = $this->getProduct->execute($sourceProductId);
                $productName = $product->name;
            }

            $query = "{$productName} produto isolado fundo branco";
            $rawBytes = $this->client->searchAndDownload($query);

            if ($rawBytes !== null) {
                $webpBytes = $this->convertToWebp($rawBytes);
                if ($webpBytes !== null) {
                    Storage::disk('public')->put($path, $webpBytes);

                    return $path;
                }
            }
        } catch (Throwable $e) {
            Log::warning("Error while trying to populate product image for source_product_id: {$sourceProductId}. Error: {$e->getMessage()}");
        }

        Log::warning("Failed to populate product image for source_product_id: {$sourceProductId}");
        $this->storePlaceholder($path);

        return $path;
    }

    private function convertToWebp(string $rawBytes): ?string
    {
        $image = @imagecreatefromstring($rawBytes);

        if ($image === false) {
            return null;
        }

        ob_start();
        imagewebp($image, null, 85);
        $webpBytes = ob_get_clean();
        imagedestroy($image);

        return $webpBytes ?: null;
    }

    private function storePlaceholder(string $path): void
    {
        $placeholder = imagecreatetruecolor(200, 200);
        $bg = imagecolorallocate($placeholder, 240, 240, 240);
        imagefill($placeholder, 0, 0, $bg);

        ob_start();
        imagewebp($placeholder, null, 80);
        $placeholderBytes = ob_get_clean();
        imagedestroy($placeholder);

        Storage::disk('public')->put($path, $placeholderBytes);
    }
}
