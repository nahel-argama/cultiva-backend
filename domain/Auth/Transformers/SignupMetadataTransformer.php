<?php

declare(strict_types=1);

namespace Cultiva\Auth\Transformers;

use Cultiva\Auth\DTO\SignupMetadataDTO;

final class SignupMetadataTransformer
{
    /**
     * @return array<string, array<string, string>>
     */
    public function transform(SignupMetadataDTO $dto): array
    {
        return [
            'profile_type'     => $dto->profileType,
            'activity_segment' => $dto->activitySegment,
            'business_type'    => $dto->businessType,
            'cnh_category'     => $dto->cnhCategory,
            'cargo_type'       => $dto->cargoType,
        ];
    }
}
