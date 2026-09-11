<?php

namespace Cultiva\Models\Offer\Http\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\Offer\Actions\CreateOfferAction;
use Cultiva\Models\Offer\Actions\GetOfferAction;
use Cultiva\Models\Offer\Actions\ListOffersAction;
use Cultiva\Models\Offer\Actions\UpdateOfferAction;
use Cultiva\Models\Offer\DTO\CreateOfferDTO;
use Cultiva\Models\Offer\DTO\UpdateOfferDTO;
use Cultiva\Models\Offer\Http\Requests\ListOffersRequest;
use Cultiva\Models\Offer\Http\Requests\StoreOfferRequest;
use Cultiva\Models\Offer\Http\Requests\UpdateOfferRequest;
use Cultiva\Models\Offer\Http\Resources\OfferResource;
use Cultiva\Models\Offer\Transformers\OfferTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class OfferController extends Controller
{
    public function index(
        ListOffersRequest $request,
        ListOffersAction $action,
    ): AnonymousResourceCollection {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 15);
        $offers = $action->execute($request->user(), $page, $perPage);

        return OfferResource::collection($offers);
    }

    public function store(
        StoreOfferRequest $request,
        CreateOfferAction $action,
        OfferTransformer $transformer,
    ): JsonResponse {
        $producer = $request->user()->producer()->firstOrFail();
        $offer = $action->execute($producer, CreateOfferDTO::from($request->validated()));

        return response()->json([
            'data' => $transformer->transform($offer->load('category')),
        ], 201);
    }

    public function show(
        Request $request,
        int $offer,
        GetOfferAction $action,
        OfferTransformer $transformer,
    ): JsonResponse {
        $producer = $request->user()->producer()->firstOrFail();
        $result = $action->execute($producer, $offer);

        return response()->json([
            'data' => $transformer->transform($result),
        ]);
    }

    public function update(
        UpdateOfferRequest $request,
        int $offer,
        UpdateOfferAction $action,
        OfferTransformer $transformer,
    ): JsonResponse {
        $producer = $request->user()->producer()->firstOrFail();
        $result = $action->execute($producer, $offer, UpdateOfferDTO::from($request->validated()));

        return response()->json([
            'data' => $transformer->transform($result),
        ]);
    }
}
