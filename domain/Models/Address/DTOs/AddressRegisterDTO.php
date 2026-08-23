<?php

namespace Cultiva\Models\Address\DTOs;

final class AddressRegisterDTO
{

    public function __construct(
        public readonly string  $zip,
        public readonly string  $number,
        public readonly ?string $complement = null,
        public readonly ?string $referencePoint = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            zip: $data['zip'],
            number: $data['number'],
            complement: $data['complement'] ?? null,
            referencePoint: $data['reference_point'] ?? null,
        );
    }
}
