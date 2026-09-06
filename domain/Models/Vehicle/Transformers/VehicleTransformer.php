<?php

namespace Cultiva\Models\Vehicle\Transformers;

use Cultiva\Models\Vehicle\Vehicle;

final class VehicleTransformer
{
    public function transform(Vehicle $vehicle): array
    {
        return [
            'plate'      => $vehicle->plate,
            'cargo_type' => $vehicle->cargo_type->value,
        ];
    }
}
