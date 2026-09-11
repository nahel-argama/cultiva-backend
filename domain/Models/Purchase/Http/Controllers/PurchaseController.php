<?php

namespace Cultiva\Models\Purchase\Http\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\Purchase\Actions\CreatePurchaseAction;
use Cultiva\Models\Purchase\Actions\ListProducerSalesAction;
use Cultiva\Models\Purchase\Actions\ListRetailerPurchasesAction;
use Cultiva\Models\Purchase\DTO\CreatePurchaseDTO;
use Cultiva\Models\Purchase\Http\Requests\ListPurchasesRequest;
use Cultiva\Models\Purchase\Http\Requests\StorePurchaseRequest;
use Cultiva\Models\Purchase\Http\Resources\PurchaseResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class PurchaseController extends Controller
{
    public function store(
        StorePurchaseRequest $request,
        CreatePurchaseAction $action,
        int $offer,
    ): JsonResponse {
        $retailer = $request->user()->retailer()->firstOrFail();
        $purchase = $action->execute($retailer, $offer, CreatePurchaseDTO::from($request->validated()));

        return response()->json([
            'data' => (new PurchaseResource($purchase))->resolve($request),
        ], 201);
    }

    public function index(
        ListPurchasesRequest $request,
        ListRetailerPurchasesAction $action,
    ): AnonymousResourceCollection {
        $paginator = $action->execute(
            $request->user()->retailer()->firstOrFail(),
            $request->integer('page', 1),
            $request->integer('per_page', 15),
        );

        return PurchaseResource::collection($paginator);
    }

    public function sales(
        ListPurchasesRequest $request,
        ListProducerSalesAction $action,
    ): AnonymousResourceCollection {
        $paginator = $action->execute(
            $request->user()->producer()->firstOrFail(),
            $request->integer('page', 1),
            $request->integer('per_page', 15),
        );

        return PurchaseResource::collection($paginator);
    }
}
