<?php

namespace Cultiva\Models\Vehicle\Actions;

use Cultiva\Models\CargoType\CargoType;
use Cultiva\Models\Vehicle\DTOs\VehicleRegisterDTO;
use Cultiva\Models\Vehicle\Vehicle;

class CreateVehicleAction
{
    public function execute(int $deliveryId, VehicleRegisterDTO $dto): Vehicle
    {
        $cargoType = CargoType::query()->where('code', $dto->cargoType->value)->firstOrFail();

        return Vehicle::query()->create([
            'delivery_id'   => $deliveryId,
            'plate'         => $dto->plate,
            'cargo_type_id' => $cargoType->id,
        ]);
    }
}
