<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class GoLogDriverResponseDTO
{
    public function __construct(
        public string $id,
        public string $cnhNumber,
        public string $cnhExpiration,
        public float $costPerHour,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            cnhNumber: $data['cnhNumber'],
            cnhExpiration: $data['cnhExpiration'],
            costPerHour: $data['costPerHour'],
        );
    }
}
