<?php

namespace Cultiva\Auth\Transformers;

use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Models\Producer\Transformers\ProducerTransformer;
use Cultiva\Models\Retailer\Transformers\RetailerTransformer;
use Cultiva\Models\User\Transformers\UserTransformer;

final class UserAuthTransformer
{
    public function __construct(
        private readonly UserTransformer $userTransformer,
        private readonly AuthTokensTransformer $authTokensTransformer,
        private readonly ProducerTransformer $producerTransformer,
        private readonly RetailerTransformer $retailerTransformer,
    ) {}

    public function transform(ProfileResultDTO $dto): array
    {
        $result = [
            'user' => $this->userTransformer->transform($dto->user),
            'profile_type' => $dto->profileType->value,
            'tokens' => $this->authTokensTransformer->transform($dto->tokens),
        ];

        if ($dto->producer !== null) {
            $result['producer'] = $this->producerTransformer->transform($dto->producer);
        }

        if ($dto->retailer !== null) {
            $result['retailer'] = $this->retailerTransformer->transform($dto->retailer);
        }

        return $result;
    }
}
