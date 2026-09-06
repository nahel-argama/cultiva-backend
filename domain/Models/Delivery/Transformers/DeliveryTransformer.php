<?php

namespace Cultiva\Models\Delivery\Transformers;

use Cultiva\Models\Delivery\Delivery;
use Cultiva\Models\Vehicle\Transformers\VehicleTransformer;

final class DeliveryTransformer
{
    public function __construct(
        private readonly VehicleTransformer $vehicleTransformer,
    ) {}

    public function transform(Delivery $delivery): array
    {
        return [
            'trade_name'   => $delivery->company->trade_name,
            'cnh_category' => $delivery->cnh_category->value,
            'vehicle'      => $this->vehicleTransformer->transform($delivery->vehicle),
        ];
    }
}
