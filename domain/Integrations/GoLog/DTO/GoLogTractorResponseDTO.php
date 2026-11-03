<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

use Cultiva\Integrations\GoLog\Enums\GoLogFuelType;
use Cultiva\Integrations\GoLog\Enums\GoLogVehicleStatus;

final readonly class GoLogTractorResponseDTO
{
    public function __construct(
        public string $id,
        public string $plate,
        public string $renavam,
        public string $model,
        public int $numberAxles,
        public float $maximumCapacity,
        public GoLogVehicleStatus $status,
        public GoLogFuelType $typeFuel,
        public float $costPerKilometer,
        public string $companyId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            plate: $data['plate'],
            renavam: $data['renavam'],
            model: $data['model'],
            numberAxles: $data['numberAxles'],
            maximumCapacity: $data['maximumCapacity'],
            status: GoLogVehicleStatus::from($data['status']),
            typeFuel: GoLogFuelType::from($data['typeFuel']),
            costPerKilometer: $data['costPerKilometer'],
            companyId: $data['companyId'],
        );
    }
}
