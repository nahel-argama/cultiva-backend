<?php

namespace Cultiva\Models\DeliveryTrip\Actions;

use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ListFinishedTripsAction
{
    public function execute(Delivery $delivery, int $page = 1, int $perPage = 15): LengthAwarePaginator
    {
        return DeliveryTrip::query()
            ->with(['stops.deliveryAddress', 'orders', 'cargoType'])
            ->where('delivery_id', $delivery->id)
            ->where('status', DeliveryTripStatus::COMPLETED)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->paginate(perPage: $perPage, page: $page);
    }
}
