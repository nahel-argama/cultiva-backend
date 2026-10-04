<?php

namespace Cultiva\Models\DeliveryTrip\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class AssignTripAction
{
    public function execute(Delivery $delivery, int $tripId): DeliveryTrip
    {
        $lock = Cache::lock("delivery_trip_assign_{$tripId}", 5);

        if (! $lock->get()) {
            throw new CultivaException(409, 'Trip is currently being assigned.');
        }

        try {
            $trip = DeliveryTrip::findOrFail($tripId);

            $vehicle = $delivery->vehicle;

            if (! $vehicle) {
                throw new CultivaException(422, 'Courier does not have a registered vehicle.');
            }

            $hasActiveTrip = DeliveryTrip::query()
                ->where('delivery_id', $delivery->id)
                ->whereIn('status', [DeliveryTripStatus::ASSIGNED, DeliveryTripStatus::IN_PROGRESS])
                ->exists();

            if ($hasActiveTrip) {
                throw new CultivaException(409, 'Courier already has an active trip.');
            }

            if ($trip->status !== DeliveryTripStatus::AVAILABLE) {
                throw new CultivaException(409, 'Trip is not available.');
            }

            if ($trip->cargo_type_id !== $vehicle->cargo_type_id) {
                throw new CultivaException(422, 'Vehicle cargo type is incompatible with this trip.');
            }

            return DB::transaction(function () use ($trip, $delivery, $vehicle): DeliveryTrip {
                $trip->update([
                    'status' => DeliveryTripStatus::ASSIGNED,
                    'delivery_id' => $delivery->id,
                    'vehicle_id' => $vehicle->id,
                ]);

                $trip->orders()->update([
                    'status' => DeliveryOrderStatus::ASSIGNED,
                ]);

                return $trip->fresh(['stops.deliveryAddress', 'orders', 'cargoType']);
            });
        } finally {
            $lock->release();
        }
    }
}
