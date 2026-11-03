<?php

namespace Cultiva\Models\Delivery\DTOs;

use Cultiva\Models\Vehicle\DTOs\GoLogVehicleRegisterDTO;

final readonly class GoLogDriverRegisterDTO
{
    public function __construct(
        public string $cpf,
        public string $cnhNumber,
        public string $cnhExpiration,
        public float $costPerHour,
        public GoLogVehicleRegisterDTO $vehicle,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            cpf: $data['cpf'],
            cnhNumber: $data['cnh_number'],
            cnhExpiration: $data['cnh_expiration'],
            costPerHour: $data['cost_per_hour'],
            vehicle: GoLogVehicleRegisterDTO::fromArray($data['vehicle']),
        );
    }
}
