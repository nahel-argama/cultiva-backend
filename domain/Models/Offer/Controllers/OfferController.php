<?php

namespace Cultiva\Models\Offer\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\Offer\Actions\CreateOfferAction;
use Cultiva\Models\Offer\Actions\GetOfferAction;
use Cultiva\Models\Offer\Actions\ListOffersAction;
use Cultiva\Models\Offer\Actions\UpdateOfferAction;
use Cultiva\Models\Offer\DTO\CreateOfferDTO;
use Cultiva\Models\Offer\DTO\UpdateOfferDTO;
use Cultiva\Models\Offer\Requests\ListOffersRequest;
use Cultiva\Models\Offer\Requests\StoreOfferRequest;
use Cultiva\Models\Offer\Requests\UpdateOfferRequest;
use Cultiva\Models\Offer\Resources\OfferResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

final class OfferController extends Controller
{
    public function index(
        ListOffersRequest $request,
        ListOffersAction $action,
    ): AnonymousResourceCollection {
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 15);
        $user = $request->user();

        $offers = $action->execute($user, $page, $perPage);

        return OfferResource::collection($offers);
    }

    public function store(
        StoreOfferRequest $request,
        CreateOfferAction $action,
    ): JsonResource {
        $producer = $request->user()
            ->producer()
            ->first();

        $dto = CreateOfferDTO::from($request->validated());

        $offer = $action->execute($producer, $dto);

        $offer->load('category');

        return new OfferResource($offer);
    }

    public function show(
        Request $request,
        int $offer,
        GetOfferAction $action,
    ): JsonResource {
        $user = $request->user();

        $result = $action->execute($user, $offer);

        return new OfferResource($result);
    }

    public function update(
        UpdateOfferRequest $request,
        int $offer,
        UpdateOfferAction $action,
    ): JsonResource {
        $producer = $request->user()
            ->producer()
            ->first();

        $dto = UpdateOfferDTO::from($request->validated());

        $result = $action->execute($producer, $offer, $dto);

        return new OfferResource($result);
    }
}
