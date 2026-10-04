<?php

namespace Cultiva\Models\DeliveryTrip\Http\Resources;

use Cultiva\Models\DeliveryTrip\DeliveryTripStop;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryTripStop
 */
final class DeliveryTripStopResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sequence' => $this->sequence,
            'stop_type' => $this->stop_type->value,
            'status' => $this->status->value,
            'address' => new DeliveryAddressResource($this->whenLoaded('deliveryAddress')),
            'arrived_at' => $this->arrived_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'notes' => $this->notes,
        ];
    }
}
