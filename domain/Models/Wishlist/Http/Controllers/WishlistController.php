<?php

namespace Cultiva\Models\Wishlist\Http\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\Wishlist\Actions\AddWishlistItemAction;
use Cultiva\Models\Wishlist\Actions\ListWishlistItemsAction;
use Cultiva\Models\Wishlist\Actions\RemoveWishlistItemAction;
use Cultiva\Models\Wishlist\Actions\GetWishlistAnalyticsAction;
use Cultiva\Models\Wishlist\DTO\AddWishlistItemDTO;
use Cultiva\Models\Wishlist\DTO\GetWishlistAnalyticsDTO;
use Cultiva\Models\Wishlist\DTO\ListWishlistItemsDTO;
use Cultiva\Models\Wishlist\Http\Requests\AddWishlistItemRequest;
use Cultiva\Models\Wishlist\Http\Requests\ListWishlistItemsRequest;
use Cultiva\Models\Wishlist\Http\Requests\GetWishlistAnalyticsRequest;
use Cultiva\Models\Wishlist\Http\Resources\WishlistAnalyticsResource;
use Cultiva\Models\Wishlist\Http\Resources\WishlistItemResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class WishlistController extends Controller
{
    public function store(AddWishlistItemRequest $request, AddWishlistItemAction $action): JsonResponse
    {
        $retailer = $request->user()->retailer()->first();

        $dto = AddWishlistItemDTO::from($request->validated());

        $item = $action->execute(
            $retailer,
            $dto,
        );

        return response()->json([
            'data' => (new WishlistItemResource($item))->resolve($request),
        ], 201);
    }

    public function index(ListWishlistItemsRequest $request, ListWishlistItemsAction $action): AnonymousResourceCollection
    {
        $retailer = $request->user()->retailer()->firstOrFail();
        $dto = ListWishlistItemsDTO::from($request->validated());

        $paginator = $action->execute(
            $retailer,
            dto: $dto,
        );

        return WishlistItemResource::collection($paginator);
    }

    public function destroy(
        int $wishlistItem,
        RemoveWishlistItemAction $action,
        Request $request,
    ): Response {
        $retailer = $request->user()->retailer()->firstOrFail();
        $action->execute($retailer, $wishlistItem);

        return response()->noContent();
    }

    public function analytics(
        GetWishlistAnalyticsRequest $request,
        GetWishlistAnalyticsAction $action,
    ): WishlistAnalyticsResource {
        $result = $action->execute(
            $request->user(),
            GetWishlistAnalyticsDTO::from($request->validated()),
        );

        return new WishlistAnalyticsResource($result);
    }
}
