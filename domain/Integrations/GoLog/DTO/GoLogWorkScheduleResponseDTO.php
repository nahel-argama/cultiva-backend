<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

use Cultiva\Integrations\GoLog\Enums\GoLogWorkScheduleStatus;

final readonly class GoLogWorkScheduleResponseDTO
{
    public function __construct(
        public string $id,
        public ?string $scheduleDate = null,
        public ?GoLogWorkScheduleStatus $status = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $status = null;
        if (isset($data['status'])) {
            $status = $data['status'] instanceof GoLogWorkScheduleStatus
                ? $data['status']
                : GoLogWorkScheduleStatus::tryFrom((string) $data['status']);
        }

        return new self(
            id: $data['id'],
            scheduleDate: $data['scheduleDate'] ?? null,
            status: $status,
        );
    }
}
