<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Cultiva\Models\Category\Category;
use Cultiva\Models\Offer\DTO\UpdateOfferDTO;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Producer\Producer;
use Illuminate\Support\Facades\Lang;

final class UpdateOfferAction
{
    public function __construct(
        private readonly GetOfferAction $getOffer,
        private readonly GetProductAction $getProduct,
    ) {}

    public function execute(Producer $producer, int $offerId, UpdateOfferDTO $data): Offer
    {
        $offer = $this->getOffer->execute($producer, $offerId);
        $categoryId = $data->categoryId ?? $offer->category_id;
        $totalQuantity = $data->totalQuantity ?? $offer->total_quantity;

        if (! Category::whereKey($categoryId)->exists()) {
            throw new CultivaException(422, Lang::get('offers.category_not_found'));
        }

        if ($totalQuantity < $offer->reserved_quantity) {
            throw new CultivaException(422, Lang::get('offers.invalid_stock'));
        }

        $product = $this->getProduct->execute($data->sourceProductId ?? $offer->source_product_id);

        $offer->fill([
            'category_id' => $categoryId,
            'source_product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $data->unitPrice ?? $offer->unit_price,
            'total_quantity' => $totalQuantity,
            'status' => $data->status ?? $offer->status,
        ])->save();

        return $offer->load('category');
    }
}
