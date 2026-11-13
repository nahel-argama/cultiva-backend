<?php

namespace Cultiva\Models\Offer\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\Offer\Actions\DeleteOfferImageAction;
use Cultiva\Models\Offer\Actions\UploadOfferImagesAction;
use Cultiva\Models\Offer\DTO\OfferImageDTO;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Offer\Requests\UploadOfferImagesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

final class OfferImageController extends Controller
{
    public function store(
        UploadOfferImagesRequest $request,
        Offer $offer,
        UploadOfferImagesAction $action,
    ): JsonResponse {
        Gate::authorize('update', $offer);

        $images = $request->file('images') ?? [];
        $result = $action->execute($offer, $images);

        return response()->json([
            'data' => array_map(fn (OfferImageDTO $img): array => $img->toArray(), $result),
        ]);
    }

    public function destroy(
        Offer $offer,
        string $image,
        DeleteOfferImageAction $action,
    ): Response {
        Gate::authorize('deleteImage', $offer);

        $action->execute($offer, $image);

        return response()->noContent();
    }
}
