<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class OptimizeRoutesResultDTO
{
    /**
     * @param array<int, string> $shipmentIds
     * @param array<int, string> $workScheduleIds
     */
    public function __construct(
        public int $shipmentsOptimized,
        public int $workSchedulesUsed,
        public array $shipmentIds,
        public array $workScheduleIds,
        public string $response,
    ) {}
}
