<?php

namespace Cultiva\Models\Wishlist\Transformers;

use Cultiva\Models\Wishlist\DTO\WishlistAnalyticsDTO;

final class WishlistAnalyticsTransformer
{
    public function transform(WishlistAnalyticsDTO $dto): array
    {
        $data = [
            'state' => $dto->state,
            'total_items' => $dto->totalItems,
            'results' => array_map(static fn ($result): array => [
                'position' => $result->position,
                'source_product_id' => $result->sourceProductId,
                'product_name' => $result->productName,
                'total' => $result->total,
                'percentage' => $result->percentage,
            ], $dto->results),
        ];

        if ($dto->others !== null) {
            $data['others'] = [
                'total' => $dto->others->total,
                'percentage' => $dto->others->percentage,
            ];
        }

        return $data;
    }
}
