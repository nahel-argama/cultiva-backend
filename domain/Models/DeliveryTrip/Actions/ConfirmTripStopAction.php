<?php

namespace Cultiva\Models\DeliveryTrip\Actions;

use Carbon\CarbonImmutable;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\DeliveryOrder\Enums\DeliveryOrderStatus;
use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Cultiva\Models\DeliveryTrip\DeliveryTripStop;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopStatus;
use Cultiva\Models\DeliveryTrip\Enums\DeliveryTripStopType;
use Illuminate\Support\Facades\DB;

final class ConfirmTripStopAction
{
    public function execute(Delivery $delivery, int $stopId): DeliveryTripStop
    {
        $stop = DeliveryTripStop::findOrFail($stopId);
        $trip = $stop->trip;

        if ($trip->delivery_id !== $delivery->id) {
            throw new CultivaException(403, 'You are not assigned to this stop.');
        }

        if ($stop->status === DeliveryTripStopStatus::COMPLETED) {
            throw new CultivaException(422, 'Stop is already completed.');
        }

        $previousIncomplete = $trip->stops()
            ->where('sequence', '<', $stop->sequence)
            ->where('status', '!=', DeliveryTripStopStatus::COMPLETED)
            ->exists();

        if ($previousIncomplete) {
            throw new CultivaException(422, 'Previous stops must be completed first.');
        }

        return DB::transaction(function () use ($stop, $trip): DeliveryTripStop {
            $now = CarbonImmutable::now();

            $stop->update([
                'status' => DeliveryTripStopStatus::COMPLETED,
                'arrived_at' => $stop->arrived_at ?? $now,
                'completed_at' => $now,
            ]);

            match ($stop->stop_type) {
                DeliveryTripStopType::PICKUP => $this->handlePickup($stop, $trip, $now),
                DeliveryTripStopType::DROPOFF => $this->handleDropoff($stop, $now),
            };

            $hasPendingStops = $trip->stops()
                ->where('status', '!=', DeliveryTripStopStatus::COMPLETED)
                ->exists();

            if (! $hasPendingStops) {
                $trip->update([
                    'status' => DeliveryTripStatus::COMPLETED,
                    'completed_at' => $now,
                ]);
            }

            return $stop->fresh(['deliveryAddress', 'trip']);
        });
    }

    private function handlePickup(DeliveryTripStop $stop, DeliveryTrip $trip, CarbonImmutable $now): void
    {
        $stop->pickupOrders()->update([
            'status' => DeliveryOrderStatus::IN_TRANSIT,
            'picked_up_at' => $now,
        ]);

        if ($trip->status === DeliveryTripStatus::ASSIGNED) {
            $trip->update([
                'status' => DeliveryTripStatus::IN_PROGRESS,
                'started_at' => $trip->started_at ?? $now,
            ]);
        }
    }

    private function handleDropoff(DeliveryTripStop $stop, CarbonImmutable $now): void
    {
        $stop->dropoffOrders()->update([
            'status' => DeliveryOrderStatus::DELIVERED,
            'delivered_at' => $now,
        ]);
    }
}
