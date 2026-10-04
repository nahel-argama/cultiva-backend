<?php

namespace Cultiva\Models\DeliveryTrip\Actions;

use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;

final class GetCurrentTripAction
{
    public function execute(Delivery $delivery): ?DeliveryTrip
    {
        return DeliveryTrip::query()
            ->with(['stops.deliveryAddress', 'orders', 'cargoType'])
            ->where('delivery_id', $delivery->id)
            ->whereIn('status', [DeliveryTripStatus::ASSIGNED, DeliveryTripStatus::IN_PROGRESS])
            ->first();
    }
}
