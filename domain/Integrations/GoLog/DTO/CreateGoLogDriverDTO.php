<?php

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class CreateGoLogDriverDTO
{
    public function __construct(
        public string $cnhNumber,
        public string $cnhExpiration,
        public float $costPerHour,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            cnhNumber: $data['cnh_number'],
            cnhExpiration: $data['cnh_expiration'],
            costPerHour: $data['cost_per_hour'],
        );
    }
}
