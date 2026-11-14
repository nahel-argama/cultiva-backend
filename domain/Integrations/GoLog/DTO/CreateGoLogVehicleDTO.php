<?php

namespace Cultiva\Integrations\GoLog\DTO;

use Cultiva\Integrations\GoLog\Enums\GoLogFuelType;
use Cultiva\Integrations\GoLog\Enums\GoLogVehicleType;

final readonly class CreateGoLogVehicleDTO
{
    public function __construct(
        public string $plate,
        public float $capacity,
        public string $renavam,
        public string $model,
        public int $numberAxles,
        public GoLogFuelType $typeFuel,
        public float $costPerKilometer,
        public ?GoLogVehicleType $vehicleType = null,
        public ?float $cargoVolume = null,
        public ?float $kmPerLiter = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            plate: $data['plate'],
            capacity: $data['capacity'],
            renavam: $data['renavam'],
            model: $data['model'],
            numberAxles: $data['number_axles'],
            typeFuel: GoLogFuelType::from($data['type_fuel']),
            costPerKilometer: $data['cost_per_kilometer'],
            vehicleType: isset($data['vehicle_type']) ? GoLogVehicleType::from($data['vehicle_type']) : null,
            cargoVolume: $data['cargo_volume'] ?? null,
            kmPerLiter: $data['km_per_liter'] ?? null,
        );
    }
}
