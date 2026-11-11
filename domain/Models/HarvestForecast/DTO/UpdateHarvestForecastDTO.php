<?php

namespace Cultiva\Models\HarvestForecast\DTO;

final readonly class UpdateHarvestForecastDTO
{
    public function __construct(
        public ?string $sourceProductId,
        public ?int $quantity,
        public ?string $startDate,
        public ?string $endDate,
    ) {}

    /** @param array{source_product_id?: string, quantity?: int, start_date?: string, end_date?: string} $data */
    public static function from(array $data): self
    {
        return new self(
            sourceProductId: $data['source_product_id'] ?? null,
            quantity: $data['quantity'] ?? null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
        );
    }
}
