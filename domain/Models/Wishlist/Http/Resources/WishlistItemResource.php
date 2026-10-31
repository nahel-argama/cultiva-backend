<?php

namespace Cultiva\Models\Wishlist\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class WishlistItemResource extends JsonResource
{
    private const SUPPORTED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_product_id' => $this->source_product_id,
            'product_name' => $this->product_name,
            'image_url' => $this->resolveImageUrl(),
            'state' => $this->state,
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    private function resolveImageUrl(): ?string
    {
        foreach (self::SUPPORTED_EXTENSIONS as $ext) {
            $path = "products/{$this->source_product_id}.{$ext}";
            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->url($path);
            }
        }

        return null;
    }
}
