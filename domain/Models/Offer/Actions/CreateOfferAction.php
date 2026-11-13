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
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

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

        $imagePath = $data->image?->store('offers', 'public');
        if ($data->image !== null && $imagePath === false) {
            throw new RuntimeException('Não foi possível salvar a imagem da oferta.');
        }

        try {
            return $producer->offers()->create([
                'category_id' => $data->categoryId,
                'source_product_id' => $product->id,
                'product_name' => $product->name,
                'unit_price' => $data->unitPrice,
                'total_quantity' => $data->totalQuantity,
                'reserved_quantity' => 0,
                'status' => OfferStatus::ACTIVE,
                'harvest_date' => $data->harvestDate,
                'lifespan_days' => $data->lifespanDays,
                'image_path' => $imagePath,
            ]);
        } catch (Throwable $e) {
            if ($imagePath) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $e;
        }
    }
}
