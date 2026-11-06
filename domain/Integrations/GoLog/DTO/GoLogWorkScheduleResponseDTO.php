<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

use Cultiva\Integrations\GoLog\Enums\GoLogWorkScheduleStatus;

final readonly class GoLogWorkScheduleResponseDTO
{
    public function __construct(
        public string $id,
        public string $scheduleDate,
        public GoLogWorkScheduleStatus $status,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            scheduleDate: $data['scheduleDate'],
            status: GoLogWorkScheduleStatus::from($data['status']),
        );
    }
}
