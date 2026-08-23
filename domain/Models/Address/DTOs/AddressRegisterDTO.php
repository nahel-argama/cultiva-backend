<?php

namespace Cultiva\Models\Address\DTOs;

final class AddressRegisterDTO
{

    /**
     * @param class-string $addressableType
     */
    public function __construct(
        public readonly string $addressableType,
        public readonly int $addressableId,
        public readonly string $zip,
        public readonly string $street,
        public readonly string $number,
        public readonly ?string $complement,
        public readonly ?string $referencePoint,
        public readonly string $neighborhood,
        public readonly string $city,
        public readonly string $state,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            addressableType: $data['addressable_type'],
            addressableId: $data['addressable_id'],
            zip: $data['zip'],
            street: $data['street'],
            number: $data['number'],
            complement: $data['complement'] ?? null,
            referencePoint: $data['reference_point'] ?? null,
            neighborhood: $data['neighborhood'],
            city: $data['city'],
            state: $data['state'],
            latitude: $data['latitude'],
            longitude: $data['longitude'],
        );
    }
}
