<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

use Carbon\CarbonImmutable;

final readonly class AvailableWorkSchedulesDTO
{
    /**
     * @param array<int, string> $workScheduleIds
     */
    public function __construct(
        public CarbonImmutable $scheduleDate,
        public array $workScheduleIds,
    ) {}
}
