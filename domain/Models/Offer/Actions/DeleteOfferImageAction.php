<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Offer\Offer;
use Illuminate\Support\Facades\Storage;

final class DeleteOfferImageAction
{
    public function execute(Offer $offer, string $imageId): void
    {
        $disk = Storage::disk('public');
        $directory = "product_images/{$offer->id}";

        if (! $disk->exists($directory)) {
            throw new CultivaException(404, 'Imagem não encontrada.');
        }

        $files = $disk->files($directory);
        $foundFile = null;

        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/^(\d+)_([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9]+)$/', $filename, $matches)) {
                if ($matches[2] === $imageId) {
                    $foundFile = $file;
                    break;
                }
            }
        }

        if ($foundFile === null) {
            throw new CultivaException(404, 'Imagem não encontrada.');
        }

        $disk->delete($foundFile);
    }
}
