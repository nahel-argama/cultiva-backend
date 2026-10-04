<?php

namespace Cultiva\Models\DeliveryTrip\Http\Resources;

use Cultiva\Models\DeliveryTrip\DeliveryTrip;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryTrip
 */
final class DeliveryTripResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'cargo_type' => [
                'id' => $this->cargoType->id,
                'code' => $this->cargoType->code->value,
                'name' => $this->cargoType->code->getLabel(),
            ],
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'stops' => DeliveryTripStopResource::collection($this->whenLoaded('stops')),
            'orders' => DeliveryOrderResource::collection($this->whenLoaded('orders')),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
