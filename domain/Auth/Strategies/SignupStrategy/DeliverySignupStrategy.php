<?php

declare(strict_types=1);

namespace Cultiva\Auth\Strategies\SignupStrategy;

use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\Strategies\SignupStrategy\Contracts\SignupStrategyInterface;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Delivery\Actions\CreateDeliveryAction;
use Cultiva\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class DeliverySignupStrategy implements SignupStrategyInterface
{
    public function __construct(
        private readonly CreateDeliveryAction $createDelivery,
    ) {}

    public function registerProfile(User $user, Company $company, SignUpDTO $dto): Model
    {
        $delivery = $this->createDelivery->execute($company->id, $dto->delivery);
        $delivery->setRelation('company', $company);

        return $delivery;
    }
}
