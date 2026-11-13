<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Models\Offer\DTO\OfferImageDTO;
use Illuminate\Support\Facades\Storage;

final class GetOfferImagesAction
{
    /**
     * @return list<OfferImageDTO>
     */
    public function execute(int $offerId): array
    {
        $disk = Storage::disk('public');
        $directory = "product_images/{$offerId}";

        if (! $disk->exists($directory)) {
            return [];
        }

        $files = $disk->files($directory);
        $images = [];

        foreach ($files as $file) {
            $filename = basename($file);
            if (preg_match('/^(\d+)_([a-zA-Z0-9_\-]+)\.([a-zA-Z0-9]+)$/', $filename, $matches)) {
                $images[] = new OfferImageDTO(
                    id: $matches[2],
                    sequence: (int) $matches[1],
                    url: $disk->url($file),
                    path: $file,
                );
            }
        }

        usort($images, fn (OfferImageDTO $a, OfferImageDTO $b) => $a->sequence <=> $b->sequence);

        return array_values($images);
    }
}
