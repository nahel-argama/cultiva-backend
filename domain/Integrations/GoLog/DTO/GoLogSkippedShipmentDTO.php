<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class GoLogSkippedShipmentDTO
{
    /**
     * @param array<int, string> $shipmentIds
     * @param array<int, string> $reasons
     */
    public function __construct(
        public array $shipmentIds,
        public string $label,
        public array $reasons,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            shipmentIds: $data['shipmentIds'] ?? [],
            label: $data['label'] ?? '',
            reasons: $data['reasons'] ?? [],
        );
    }
}
