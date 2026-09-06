<?php

namespace Cultiva\Models\Delivery\DTOs;

use Cultiva\Models\Delivery\Enums\CnhCategory;
use Cultiva\Models\Vehicle\DTOs\VehicleRegisterDTO;

final class DeliveryRegisterDTO
{
    public function __construct(
        public readonly string $cnhNumber,
        public readonly CnhCategory $cnhCategory,
        public readonly VehicleRegisterDTO $vehicle,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            cnhNumber: $data['cnh_number'],
            cnhCategory: CnhCategory::from($data['cnh_category']),
            vehicle: VehicleRegisterDTO::fromArray($data['vehicle']),
        );
    }
}
