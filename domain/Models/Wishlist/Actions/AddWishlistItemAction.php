<?php

namespace Cultiva\Models\Wishlist\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Cultiva\Models\Retailer\Retailer;
use Cultiva\Models\Wishlist\DTO\AddWishlistItemDTO;
use Cultiva\Models\Wishlist\WishlistItem;
use Illuminate\Support\Facades\Lang;

final class AddWishlistItemAction
{
    public function __construct(private readonly GetProductAction $getProduct) {}

    public function execute(Retailer $retailer, AddWishlistItemDTO $dto): WishlistItem
    {
        $company = $retailer->company()->with('address')->firstOrFail();

        if ($company->address === null || trim($company->address->state) === '') {
            throw new CultivaException(422, Lang::get('wishlist.address_required'));
        }

        if ($retailer->wishlistItems()->where('source_product_id', $dto->productId)->exists()) {
            throw new CultivaException(409, Lang::get('wishlist.already_exists'));
        }

        try {
            $product = $this->getProduct->execute($dto->productId);
        } catch (CultivaException $exception) {
            if ($exception->getStatusCode() === 422) {
                throw new CultivaException(404, Lang::get('wishlist.product_not_found'), $exception);
            }

            throw $exception;
        }

        return $retailer->wishlistItems()->create([
            'source_product_id' => $product->id,
            'product_name' => $product->name,
            'state' => $company->address->state,
        ]);
    }
}
