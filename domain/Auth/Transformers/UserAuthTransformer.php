<?php

namespace Cultiva\Auth\Transformers;

use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Delivery\Transformers\DeliveryTransformer;
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
        private readonly DeliveryTransformer $deliveryTransformer,
    ) {}

    public function transform(ProfileResultDTO $dto): array
    {
        $user = $dto->user;
        $profileType = $user->getProfileType();

        $profile = match ($profileType) {
            ProfileType::PRODUCER => $this->producerTransformer->transform($user->producer),
            ProfileType::RETAILER => $this->retailerTransformer->transform($user->retailer),
            ProfileType::DELIVERY => $this->deliveryTransformer->transform($user->delivery),
        };

        $result = [
            'user'         => $this->userTransformer->transform($user),
            'profile_type' => $profileType->value,
            'profile'      => $profile,
        ];

        if ($dto->tokens !== null) {
            $result['tokens'] = $this->authTokensTransformer->transform($dto->tokens);
        }

        return $result;
    }
}
