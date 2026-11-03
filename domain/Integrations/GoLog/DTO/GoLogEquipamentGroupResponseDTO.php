<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class GoLogEquipamentGroupResponseDTO
{
    public function __construct(
        public string $id,
        public ?string $observation = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            observation: $data['observation'] ?? null,
        );
    }
}
