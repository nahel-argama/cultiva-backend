<?php

namespace Cultiva\Models\DeliveryTrip\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopStatus;
use Illuminate\Support\Facades\DB;

final class CancelTripAssignmentAction
{
    public function execute(Delivery $delivery, int $tripId): DeliveryTrip
    {
        $trip = DeliveryTrip::findOrFail($tripId);

        if ($trip->status !== DeliveryTripStatus::ASSIGNED) {
            throw new CultivaException(422, 'Only assigned trips can be cancelled.');
        }

        if ($trip->delivery_id !== $delivery->id) {
            throw new CultivaException(403, 'You are not assigned to this trip.');
        }

        $hasCompletedStop = $trip->stops()
            ->where('status', '!=', DeliveryTripStopStatus::PENDING)
            ->exists();

        $hasPickedUpOrder = $trip->orders()
            ->whereIn('status', [DeliveryOrderStatus::IN_TRANSIT, DeliveryOrderStatus::DELIVERED])
            ->exists();

        if ($hasCompletedStop || $hasPickedUpOrder) {
            throw new CultivaException(422, 'Cannot cancel trip after packages have been picked up.');
        }

        return DB::transaction(function () use ($trip): DeliveryTrip {
            $trip->update([
                'status' => DeliveryTripStatus::AVAILABLE,
                'delivery_id' => null,
                'vehicle_id' => null,
            ]);

            $trip->orders()->update([
                'status' => DeliveryOrderStatus::PENDING,
            ]);

            return $trip->fresh(['stops.deliveryAddress', 'orders', 'cargoType']);
        });
    }
}
