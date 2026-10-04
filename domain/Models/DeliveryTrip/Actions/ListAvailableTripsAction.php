<?php

namespace Cultiva\Models\DeliveryTrip\Actions;

use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;

final class ListAvailableTripsAction
{
    public function execute(Delivery $delivery, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        $vehicle = $delivery->vehicle;

        if (! $vehicle) {
            return new ConcreteLengthAwarePaginator(
                items: [],
                total: 0,
                perPage: $perPage,
                currentPage: $page,
            );
        }

        return DeliveryTrip::query()
            ->with(['stops.deliveryAddress', 'cargoType'])
            ->where('status', DeliveryTripStatus::AVAILABLE)
            ->where('cargo_type_id', $vehicle->cargo_type_id)
            ->latest('id')
            ->paginate(perPage: $perPage, page: $page);
    }
}
