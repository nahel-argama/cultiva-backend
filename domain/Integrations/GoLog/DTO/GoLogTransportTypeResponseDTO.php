<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class GoLogTransportTypeResponseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public ?string $description = null,
        public ?string $care = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            description: $data['description'] ?? null,
            care: $data['care'] ?? null,
        );
    }
}
