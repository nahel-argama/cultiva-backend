<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class OptimizeRoutesResultDTO
{
    /**
     * @param array<int, string> $plannedShipmentIds
     * @param array<int, string> $skippedShipmentIds
     * @param array<int, string> $transportIds
     * @param array<int, string> $workScheduleIds
     */
    public function __construct(
        public int $shipmentsOptimized,
        public int $routesCount,
        public int $workSchedulesUsed,
        public array $plannedShipmentIds,
        public array $skippedShipmentIds,
        public array $transportIds,
        public array $workScheduleIds,
    ) {}
}
