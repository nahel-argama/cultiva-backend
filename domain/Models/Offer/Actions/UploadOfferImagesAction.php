<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Offer\DTO\OfferImageDTO;
use Cultiva\Models\Offer\Offer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class UploadOfferImagesAction
{
    private const MAX_IMAGES = 3;

    public function __construct(
        private readonly GetOfferImagesAction $getOfferImages,
    ) {}

    /**
     * @param list<UploadedFile> $images
     * @return list<OfferImageDTO>
     */
    public function execute(Offer $offer, array $images): array
    {
        if (empty($images)) {
            return $this->getOfferImages->execute($offer->id);
        }

        $existing = $this->getOfferImages->execute($offer->id);
        $totalCount = count($existing) + count($images);

        if ($totalCount > self::MAX_IMAGES) {
            throw new CultivaException(422, 'Limite máximo de 3 imagens por oferta excedido.');
        }

        $occupiedSlots = array_map(fn (OfferImageDTO $img): int => $img->sequence, $existing);
        $availableSlots = array_values(array_diff([1, 2, 3], $occupiedSlots));

        $disk = Storage::disk('public');
        $directory = "product_images/{$offer->id}";

        foreach ($images as $index => $file) {
            $slot = $availableSlots[$index];
            $id = Str::lower(Str::random(8));
            $extension = $file->getClientOriginalExtension() ?: 'jpg';
            $filename = "{$slot}_{$id}.{$extension}";

            $disk->putFileAs($directory, $file, $filename);
        }

        return $this->getOfferImages->execute($offer->id);
    }
}
