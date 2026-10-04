<?php

namespace Cultiva\Models\DeliveryTrip\Http\Resources;

use Cultiva\Models\DeliveryOrder\DeliveryAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DeliveryAddress
 */
final class DeliveryAddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'zip' => $this->zip,
            'street' => $this->street,
            'number' => $this->number,
            'complement' => $this->complement,
            'reference_point' => $this->reference_point,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
        ];
    }
}
