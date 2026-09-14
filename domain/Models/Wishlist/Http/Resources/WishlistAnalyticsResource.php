<?php

namespace Cultiva\Models\Wishlist\Http\Resources;

use Cultiva\Models\Wishlist\DTO\WishlistAnalyticsDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WishlistAnalyticsDTO */
final class WishlistAnalyticsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'state' => $this->state,
            'total_items' => $this->totalItems,
            'results' => array_map(static fn ($result): array => [
                'position' => $result->position,
                'source_product_id' => $result->sourceProductId,
                'product_name' => $result->productName,
                'total' => $result->total,
                'percentage' => $result->percentage,
            ], $this->results),
        ];

        if ($this->others !== null) {
            $data['others'] = [
                'total' => $this->others->total,
                'percentage' => $this->others->percentage,
            ];
        }

        return $data;
    }
}
