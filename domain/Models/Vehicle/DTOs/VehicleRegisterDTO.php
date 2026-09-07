<?php

namespace Cultiva\Models\Vehicle\DTOs;

use Cultiva\Models\Vehicle\Enums\CargoType;

final class VehicleRegisterDTO
{
    public function __construct(
        public readonly string $plate,
        public readonly CargoType $cargoType,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            plate: $data['plate'],
            cargoType: CargoType::from($data['cargo_type']),
        );
    }
}
