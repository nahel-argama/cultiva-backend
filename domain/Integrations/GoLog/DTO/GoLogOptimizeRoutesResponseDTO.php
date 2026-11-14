<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class GoLogOptimizeRoutesResponseDTO
{
    /**
     * @param array<int, string> $transportIds
     * @param array<int, GoLogSkippedShipmentDTO> $skipped
     */
    public function __construct(
        public int $routesCount,
        public int $plannedShipments,
        public array $transportIds,
        public array $skipped,
        public int|float $totalDistanceMeters,
        public float $totalCost,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $skippedItems = array_map(
            static fn(array $item): GoLogSkippedShipmentDTO => GoLogSkippedShipmentDTO::fromArray($item),
            $data['skipped'] ?? []
        );

        return new self(
            routesCount: $data['routesCount'] ?? 0,
            plannedShipments: $data['plannedShipments'] ?? 0,
            transportIds: $data['transportIds'] ?? [],
            skipped: $skippedItems,
            totalDistanceMeters: $data['totalDistanceMeters'] ?? 0,
            totalCost: $data['totalCost'] ?? 0.0,
        );
    }

    /**
     * @return array<int, string>
     */
    public function skippedShipmentIds(): array
    {
        $ids = [];
        foreach ($this->skipped as $item) {
            foreach ($item->shipmentIds as $shipmentId) {
                $ids[] = $shipmentId;
            }
        }

        return array_values(array_unique($ids));
    }
}
