<?php

namespace Cultiva\Models\HarvestForecast\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\HarvestForecast\Actions\CreateHarvestForecastAction;
use Cultiva\Models\HarvestForecast\Actions\DeleteHarvestForecastAction;
use Cultiva\Models\HarvestForecast\Actions\ListHarvestForecastsAction;
use Cultiva\Models\HarvestForecast\Actions\ListHarvestSeasonPeriodsAction;
use Cultiva\Models\HarvestForecast\Actions\UpdateHarvestForecastAction;
use Cultiva\Models\HarvestForecast\DTO\CreateHarvestForecastDTO;
use Cultiva\Models\HarvestForecast\DTO\ListHarvestForecastsDTO;
use Cultiva\Models\HarvestForecast\DTO\UpdateHarvestForecastDTO;
use Cultiva\Models\HarvestForecast\Requests\ListHarvestForecastsRequest;
use Cultiva\Models\HarvestForecast\Requests\ListHarvestSeasonPeriodsRequest;
use Cultiva\Models\HarvestForecast\Requests\StoreHarvestForecastRequest;
use Cultiva\Models\HarvestForecast\Requests\UpdateHarvestForecastRequest;
use Cultiva\Models\HarvestForecast\Resources\HarvestForecastResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\JsonResponse;

final class HarvestForecastController extends Controller
{
    public function seasonPeriods(
        ListHarvestSeasonPeriodsRequest $request,
        ListHarvestSeasonPeriodsAction $action,
    ): JsonResponse {
        $periods = $action->execute($request->validated('source_product_id'));

        return response()->json(['data' => $periods]);
    }

    public function index(
        ListHarvestForecastsRequest $request,
        ListHarvestForecastsAction $action,
    ): AnonymousResourceCollection {
        $filters = $request->validated();
        $filters['page'] = $request->integer('page', 1);
        $filters['per_page'] = $request->integer('per_page', 15);
        $dto = ListHarvestForecastsDTO::from($filters);
        $forecasts = $action->execute($dto);

        return HarvestForecastResource::collection($forecasts);
    }

    public function store(
        StoreHarvestForecastRequest $request,
        CreateHarvestForecastAction $action,
    ): JsonResource {
        $producer = $request->user()->producer()->firstOrFail();
        $dto = CreateHarvestForecastDTO::from($request->validated());
        $forecast = $action->execute($producer, $dto);
        $forecast->load('producer.company.address');

        return new HarvestForecastResource($forecast);
    }

    public function update(
        UpdateHarvestForecastRequest $request,
        int $forecast,
        UpdateHarvestForecastAction $action,
    ): JsonResource {
        $producer = $request->user()->producer()->firstOrFail();
        $dto = UpdateHarvestForecastDTO::from($request->validated());
        $result = $action->execute($producer, $forecast, $dto);

        return new HarvestForecastResource($result);
    }

    public function destroy(
        \Illuminate\Http\Request $request,
        int $forecast,
        DeleteHarvestForecastAction $action,
    ): Response {
        $producer = $request->user()->producer()->firstOrFail();
        $action->execute($producer, $forecast);

        return response()->noContent();
    }
}
