<?php

namespace Cultiva\Models\Offer\Http\Resources;

use Cultiva\Models\Category\Http\Resources\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class OfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'source_product_id' => $this->source_product_id,
            'product_name' => $this->product_name,
            'category' => new CategoryResource($this->category),
            'unit_price' => $this->unit_price,
            'total_quantity' => $this->total_quantity,
            'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->availableQuantity(),
            'status' => $this->status->value,
            'is_visible' => $this->isVisible(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
