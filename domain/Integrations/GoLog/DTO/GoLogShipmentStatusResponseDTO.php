<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;

final readonly class GoLogShipmentStatusResponseDTO
{
    public function __construct(
        public string $id,
        public ?GoLogOperationType $typeOperation,
        public GoLogShipmentStatus $status,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        $rawType = $data['typeOperation'] ?? null;

        return new self(
            id: (string) $data['id'],
            typeOperation: is_string($rawType) ? GoLogOperationType::tryFrom($rawType) : null,
            status: GoLogShipmentStatus::from((string) $data['status']),
        );
    }
}
