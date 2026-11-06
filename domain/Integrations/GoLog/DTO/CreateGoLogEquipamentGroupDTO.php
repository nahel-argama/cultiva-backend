<?php

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class CreateGoLogEquipamentGroupDTO
{
    public function __construct(
        public ?string $observation = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            observation: $data['observation'] ?? null,
        );
    }
}
