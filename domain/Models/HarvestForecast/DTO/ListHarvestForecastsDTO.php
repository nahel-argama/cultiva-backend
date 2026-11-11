<?php

namespace Cultiva\Models\HarvestForecast\DTO;

final readonly class ListHarvestForecastsDTO
{
    public function __construct(
        public ?string $sourceProductId,
        public ?string $dateFrom,
        public ?string $dateTo,
        public int $page,
        public int $perPage,
        public ?int $producerId,
    ) {}

    /** @param array{source_product_id?: string, date_from?: string, date_to?: string, page: int, per_page: int, producer_id?: int} $data */
    public static function from(array $data): self
    {
        return new self(
            sourceProductId: $data['source_product_id'] ?? null,
            dateFrom: $data['date_from'] ?? null,
            dateTo: $data['date_to'] ?? null,
            page: $data['page'],
            perPage: $data['per_page'],
            producerId: $data['producer_id'] ?? null,
        );
    }
}
