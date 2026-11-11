<?php

namespace Cultiva\Models\HarvestForecast\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \Cultiva\Models\HarvestForecast\HarvestForecast */
final class HarvestForecastResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'source_product_id' => $this->source_product_id,
                'name' => $this->product_name,
            ],
            'quantity' => $this->quantity,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date->toDateString(),
            'producer' => [
                'name' => $this->producer->company->trade_name,
                'state' => $this->producer->company->address->state,
            ],
        ];
    }
}
