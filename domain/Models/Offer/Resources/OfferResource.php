<?php

namespace Cultiva\Models\Offer\Resources;

use Cultiva\Models\Category\Http\Resources\CategoryResource;
use Cultiva\Models\Offer\Actions\GetOfferImagesAction;
use Cultiva\Models\Offer\DTO\OfferImageDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_product_id' => $this->source_product_id,
            'product_name' => $this->product_name,
            'image_url' => $this->image_path ? Storage::disk('public')->url($this->image_path) : null,
            'producer_name' => $this->when($this->relationLoaded('producer'), fn () => $this->producer->company->trade_name),
            'category' => new CategoryResource($this->category),
            'unit_price' => $this->unit_price,
            'total_quantity' => $this->total_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->availableQuantity(),
            'status' => $this->status->value,
            'is_visible' => $this->isVisible(),
            'harvest_date' => $this->harvest_date?->toDateString(),
            'lifespan_days' => $this->lifespan_days,
            'is_in_wishlist' => $this->whenHas('is_in_wishlist', fn (): bool => (bool) $this->is_in_wishlist),
            'distance_km' => $this->whenHas('distance_km', fn (): ?float => $this->distance_km === null ? null : (float) $this->distance_km),
            'images' => array_map(
                fn (OfferImageDTO $image): array => $image->toArray(),
                app(GetOfferImagesAction::class)->execute($this->id)
            ),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
