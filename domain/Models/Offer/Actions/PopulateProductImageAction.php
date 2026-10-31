<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Integrations\ProductImage\Clients\ProductImageClient;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PopulateProductImageAction
{
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function __construct(
        private readonly ProductImageClient $client,
        private readonly GetProductAction $getProduct,
    ) {}

    public function execute(string $sourceProductId, ?string $productName = null): string
    {
        if ($existingPath = $this->findExistingImagePath($sourceProductId)) {
            return $existingPath;
        }

        try {
            if ($productName === null) {
                $product = $this->getProduct->execute($sourceProductId);
                $productName = $product->name;
            }

            $query = "{$productName} produto isolado fundo branco";
            $downloadedImage = $this->client->searchAndDownload($query);

            if ($downloadedImage !== null) {
                $path = "products/{$sourceProductId}.{$downloadedImage->extension}";
                Storage::disk('public')->put($path, $downloadedImage->content);

                return $path;
            }
        } catch (Throwable $e) {
            Log::warning("Error while trying to populate product image for source_product_id: {$sourceProductId}. Error: {$e->getMessage()}");
        }

        Log::warning("Failed to populate product image for source_product_id: {$sourceProductId}");

        return $this->storePlaceholder($sourceProductId);
    }

    private function findExistingImagePath(string $sourceProductId): ?string
    {
        foreach (self::SUPPORTED_EXTENSIONS as $ext) {
            $path = "products/{$sourceProductId}.{$ext}";
            if (Storage::disk('public')->exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function storePlaceholder(string $sourceProductId): string
    {
        $path = "products/{$sourceProductId}.jpg";
        $placeholder = imagecreatetruecolor(200, 200);
        $bg = imagecolorallocate($placeholder, 240, 240, 240);
        imagefill($placeholder, 0, 0, $bg);

        ob_start();
        imagejpeg($placeholder, null, 80);
        $placeholderBytes = ob_get_clean();
        imagedestroy($placeholder);

        Storage::disk('public')->put($path, $placeholderBytes);

        return $path;
    }
}
