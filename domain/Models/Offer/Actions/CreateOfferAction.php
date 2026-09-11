<?php

namespace Cultiva\Models\Offer\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Cultiva\Models\Category\Category;
use Cultiva\Models\Offer\DTO\CreateOfferDTO;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Producer\Producer;
use Illuminate\Support\Facades\Lang;

final class CreateOfferAction
{
    public function __construct(
        private readonly GetProductAction $getProduct,
    ) {}

    public function execute(Producer $producer, CreateOfferDTO $data): Offer
    {
        if (! Category::whereKey($data->categoryId)->exists()) {
            throw new CultivaException(422, Lang::get('offers.category_not_found'));
        }

        $product = $this->getProduct->execute($data->sourceProductId);

        return $producer->offers()->create([
            'category_id' => $data->categoryId,
            'source_product_id' => $product->id,
            'product_name' => $product->name,
            'unit_price' => $data->unitPrice,
            'total_quantity' => $data->totalQuantity,
            'reserved_quantity' => 0,
            'status' => OfferStatus::ACTIVE,
        ]);
    }
}
