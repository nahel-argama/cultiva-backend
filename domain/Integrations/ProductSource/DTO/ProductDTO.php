<?php

namespace Cultiva\Integrations\ProductSource\DTO;

final readonly class ProductDTO
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
        );
    }
}
