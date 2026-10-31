<?php

namespace Cultiva\Models\Wishlist\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Cultiva\Models\Offer\Jobs\PopulateProductImageJob;
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
        $lock = Cache::lock("wishlist:{$retailer->id}:{$dto->productId}", 60);

        if (! $lock->get()) {
            throw new CultivaException(409, Lang::get('wishlist.in_progress'));
        }

        try {
            $company = $retailer->company()->with('address')->firstOrFail();

            try {
                $product = $this->getProduct->execute($dto->productId);
            } catch (CultivaException $exception) {
                if ($exception->getStatusCode() === 422) {
                    throw new CultivaException(404, Lang::get('wishlist.product_not_found'), $exception);
                }

                throw $exception;
            }

            if ($retailer->wishlistItems()->where('source_product_id', $product->id)->exists()) {
                throw new CultivaException(409, Lang::get('wishlist.already_exists'));
            }

            $wishlistItem = $retailer->wishlistItems()->create([
                'source_product_id' => $product->id,
                'product_name' => $product->name,
                'state' => $company->address->state,
            ]);

            PopulateProductImageJob::dispatch($product->id, $product->name);

            return $wishlistItem;
        } finally {
            $lock->release();
        }
    }
}
