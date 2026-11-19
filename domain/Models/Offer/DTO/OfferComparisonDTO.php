<?php

namespace Cultiva\Models\Offer\DTO;

use Illuminate\Database\Eloquent\Collection;

final readonly class OfferComparisonDTO
{
    public function __construct(
        public Collection $offers,
        public array $criteria,
        public ?int $recommendedOfferId,
    ) {}
}
