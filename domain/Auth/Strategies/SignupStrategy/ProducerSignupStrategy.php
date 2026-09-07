<?php

declare(strict_types=1);

namespace Cultiva\Auth\Strategies\SignupStrategy;

use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\Strategies\SignupStrategy\Contracts\SignupStrategyInterface;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Producer\Actions\CreateProducerAction;
use Cultiva\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class ProducerSignupStrategy implements SignupStrategyInterface
{
    public function __construct(
        private readonly CreateProducerAction $createProducer,
    ) {}

    public function registerProfile(User $user, Company $company, SignUpDTO $dto): Model
    {
        $producer = $this->createProducer->execute($company->id, $dto->producer);
        $producer->setRelation('company', $company);

        return $producer;
    }
}
