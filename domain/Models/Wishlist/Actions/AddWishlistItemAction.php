<?php

namespace Cultiva\Models\Wishlist\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Cultiva\Models\Retailer\Retailer;
use Cultiva\Models\Wishlist\DTO\AddWishlistItemDTO;
use Cultiva\Models\Wishlist\WishlistItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

final class AddWishlistItemAction
{
    public function __construct(private readonly GetProductAction $getProduct) {}

    public function execute(Retailer $retailer, AddWishlistItemDTO $dto): WishlistItem
    {
        $company = $retailer->company()->with('address')->firstOrFail();

        try {
            $product = $this->getProduct->execute($dto->productId);
        } catch (CultivaException $exception) {
            if ($exception->getStatusCode() === 422) {
                throw new CultivaException(404, Lang::get('wishlist.product_not_found'), $exception);
            }

            throw $exception;
        }

        $lock = Cache::lock("wishlist:{$retailer->id}:{$product->id}", 60);

        if (! $lock->get()) {
            throw new CultivaException(409, Lang::get('wishlist.already_exists'));
        }

        try {
            if ($retailer->wishlistItems()->where('source_product_id', $product->id)->exists()) {
                throw new CultivaException(409, Lang::get('wishlist.already_exists'));
            }

            return $retailer->wishlistItems()->create([
                'source_product_id' => $product->id,
                'product_name' => $product->name,
                'state' => $company->address->state,
            ]);
        } finally {
            $lock->release();
        }
    }
}
