<?php

namespace Cultiva\Models\Offer\DTO;

final readonly class OfferImageDTO
{
    public function __construct(
        public string $id,
        public int $sequence,
        public string $url,
        public string $path,
    ) {}

    /**
     * @return array{id: string, sequence: int, url: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sequence' => $this->sequence,
            'url' => $this->url,
        ];
    }
}
