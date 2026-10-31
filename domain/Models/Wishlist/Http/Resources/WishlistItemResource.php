<?php

namespace Cultiva\Models\Wishlist\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class WishlistItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_product_id' => $this->source_product_id,
            'product_name' => $this->product_name,
            'image_url' => Storage::disk('public')->url("products/{$this->source_product_id}.jpg"),
            'state' => $this->state,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
