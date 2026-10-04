<?php

namespace Cultiva\Models\DeliveryTrip\Http\Resources;

use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryOrder
 */
final class DeliveryOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'purchase_id' => $this->purchase_id,
            'trip_id' => $this->trip_id,
            'pickup_stop_id' => $this->pickup_stop_id,
            'dropoff_stop_id' => $this->dropoff_stop_id,
            'quantity' => $this->quantity,
            'status' => $this->status->value,
            'picked_up_at' => $this->picked_up_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
        ];
    }
}
