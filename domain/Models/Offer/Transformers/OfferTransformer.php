<?php

namespace Cultiva\Models\Offer\Transformers;

use Cultiva\Models\Category\Transformers\CategoryTransformer;
use Cultiva\Models\Offer\Offer;

final class OfferTransformer
{
    public function __construct(
        private readonly CategoryTransformer $categoryTransformer,
    ) {}

    public function transform(Offer $offer): array
    {
        return [
            'id' => $offer->id,
            'source_product_id' => $offer->source_product_id,
            'product_name' => $offer->product_name,
            'category' => $this->categoryTransformer->transform($offer->category),
            'unit_price' => $offer->unit_price,
            'total_quantity' => $offer->total_quantity,
            'reserved_quantity' => $offer->reserved_quantity,
            'available_quantity' => $offer->available_quantity,
            'status' => $offer->status->value,
            'is_visible' => $offer->is_visible,
            'created_at' => $offer->created_at->toISOString(),
            'updated_at' => $offer->updated_at->toISOString(),
        ];
    }
}
