<?php

declare(strict_types=1);

namespace Cultiva\Auth\Strategies\SignupStrategy;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Auth\Strategies\SignupStrategy\Contracts\SignupStrategyInterface;

class SignupStrategyFactory
{
    public function __construct(
        private readonly ProducerSignupStrategy $producerStrategy,
        private readonly RetailerSignupStrategy $retailerStrategy,
        private readonly DeliverySignupStrategy $deliveryStrategy,
    ) {}

    public function make(ProfileType $profileType): SignupStrategyInterface
    {
        return match ($profileType) {
            ProfileType::PRODUCER => $this->producerStrategy,
            ProfileType::RETAILER => $this->retailerStrategy,
            ProfileType::DELIVERY => $this->deliveryStrategy,
        };
    }
}
