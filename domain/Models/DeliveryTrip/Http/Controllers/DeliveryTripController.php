<?php

namespace Cultiva\Models\DeliveryTrip\Http\Controllers;

use Cultiva\Base\Contracts\Controller;
use Cultiva\Models\DeliveryTrip\Actions\AssignTripAction;
use Cultiva\Models\DeliveryTrip\Actions\CancelTripAssignmentAction;
use Cultiva\Models\DeliveryTrip\Actions\ConfirmTripStopAction;
use Cultiva\Models\DeliveryTrip\Actions\GetCurrentTripAction;
use Cultiva\Models\DeliveryTrip\Actions\ListAvailableTripsAction;
use Cultiva\Models\DeliveryTrip\Actions\ListFinishedTripsAction;
use Cultiva\Models\DeliveryTrip\Http\Requests\ListAvailableTripsRequest;
use Cultiva\Models\DeliveryTrip\Http\Requests\ListFinishedTripsRequest;
use Cultiva\Models\DeliveryTrip\Http\Resources\DeliveryTripResource;
use Cultiva\Models\DeliveryTrip\Http\Resources\DeliveryTripStopResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

final class DeliveryTripController extends Controller
{
    public function index(
        ListAvailableTripsRequest $request,
        ListAvailableTripsAction $action,
    ): AnonymousResourceCollection {
        $delivery = $request->user()->delivery()->firstOrFail();
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 15);

        $trips = $action->execute($delivery, $page, $perPage);

        return DeliveryTripResource::collection($trips);
    }

    public function finished(
        ListFinishedTripsRequest $request,
        ListFinishedTripsAction $action,
    ): AnonymousResourceCollection {
        $delivery = $request->user()->delivery()->firstOrFail();
        $page = $request->integer('page', 1);
        $perPage = $request->integer('per_page', 15);

        $trips = $action->execute($delivery, $page, $perPage);

        return DeliveryTripResource::collection($trips);
    }

    public function current(
        Request $request,
        GetCurrentTripAction $action,
    ): JsonResponse|JsonResource {
        $delivery = $request->user()->delivery()->firstOrFail();
        $trip = $action->execute($delivery);

        if (! $trip) {
            return response()->json(['data' => null]);
        }

        return new DeliveryTripResource($trip);
    }

    public function assign(
        int $trip,
        Request $request,
        AssignTripAction $action,
    ): JsonResource {
        $delivery = $request->user()->delivery()->firstOrFail();
        $assignedTrip = $action->execute($delivery, $trip);

        return new DeliveryTripResource($assignedTrip);
    }

    public function cancel(
        int $trip,
        Request $request,
        CancelTripAssignmentAction $action,
    ): JsonResource {
        $delivery = $request->user()->delivery()->firstOrFail();
        $cancelledTrip = $action->execute($delivery, $trip);

        return new DeliveryTripResource($cancelledTrip);
    }

    public function confirmStop(
        int $stop,
        Request $request,
        ConfirmTripStopAction $action,
    ): JsonResource {
        $delivery = $request->user()->delivery()->firstOrFail();
        $confirmedStop = $action->execute($delivery, $stop);

        return new DeliveryTripStopResource($confirmedStop);
    }
}
