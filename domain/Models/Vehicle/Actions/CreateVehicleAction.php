<?php

namespace Cultiva\Models\Vehicle\Actions;

use Cultiva\Models\Vehicle\DTOs\VehicleRegisterDTO;
use Cultiva\Models\Vehicle\Vehicle;

class CreateVehicleAction
{
    public function execute(int $deliveryId, VehicleRegisterDTO $dto): Vehicle
    {
        return Vehicle::query()->create([
            'delivery_id' => $deliveryId,
            'plate'       => $dto->plate,
            'cargo_type'  => $dto->cargoType->value,
        ]);
    }
}
