<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class DispatchBatchResultDTO
{
    /**
     * @param array<string, int> $byCategory
     */
    public function __construct(
        public int $totalOrders,
        public int $batchesSent,
        public int $shipmentsCreated,
        public array $byCategory,
    ) {}
}
