<?php

namespace Cultiva\Models\Offer\Http\Controllers;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\Offer\Actions\CreateOfferAction;
use Cultiva\Models\Offer\Actions\GetOfferAction;
use Cultiva\Models\Offer\Actions\ListAvailableOffersAction;
use Cultiva\Models\Offer\Actions\ListProducerOffersAction;
use Cultiva\Models\Offer\Actions\UpdateOfferAction;
use Cultiva\Models\Offer\DTO\CreateOfferDTO;
use Cultiva\Models\Offer\DTO\UpdateOfferDTO;
use Cultiva\Models\Offer\Http\Requests\ListOffersRequest;
use Cultiva\Models\Offer\Http\Requests\StoreOfferRequest;
use Cultiva\Models\Offer\Http\Requests\UpdateOfferRequest;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Offer\Transformers\OfferTransformer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OfferController extends Controller
{
    public function index(
        ListOffersRequest $request,
        ListProducerOffersAction $producerAction,
        ListAvailableOffersAction $availableAction,
        OfferTransformer $transformer,
    ): JsonResponse {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 15);
        $offers = match ($request->user()->profile_type) {
            ProfileType::PRODUCER => $producerAction->execute(
                $request->user()->producer()->firstOrFail(),
                $page,
                $perPage,
            ),
            ProfileType::RETAILER => $availableAction->execute($page, $perPage),
        };

        return response()->json([
            'data' => $offers->through(
                fn (Offer $offer): array => $transformer->transform($offer),
            )->items(),
            'current_page' => $offers->currentPage(),
            'per_page' => $offers->perPage(),
            'total' => $offers->total(),
            'has_previous_page' => ! $offers->onFirstPage(),
            'has_next_page' => $offers->hasMorePages(),
        ]);
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
