<?php

declare(strict_types=1);

namespace Cultiva\Auth\DTO;

final readonly class SignupMetadataDTO
{
    /**
     * @param array<string, string> $profileType
     * @param array<string, string> $activitySegment
     * @param array<string, string> $businessType
     * @param array<string, string> $cnhCategory
     * @param array<string, string> $cargoType
     */
    public function __construct(
        public array $profileType,
        public array $activitySegment,
        public array $businessType,
        public array $cnhCategory,
        public array $cargoType,
    ) {}
}
