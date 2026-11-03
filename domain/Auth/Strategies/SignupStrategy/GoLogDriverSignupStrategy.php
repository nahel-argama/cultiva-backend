<?php

declare(strict_types=1);

namespace Cultiva\Auth\Strategies\SignupStrategy;

use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\Strategies\SignupStrategy\Contracts\SignupStrategyInterface;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Delivery\Actions\CreateGoLogDriverAction;
use Cultiva\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class GoLogDriverSignupStrategy implements SignupStrategyInterface
{
    public function __construct(
        private readonly CreateGoLogDriverAction $createGoLogDriver,
    ) {}

    public function registerProfile(User $user, Company $company, SignUpDTO $dto): Model
    {
        $gologDriver = $this->createGoLogDriver->execute(
            user: $user,
            companyId: $company->id,
            password: $dto->user->password,
            dto: $dto->gologDriver,
        );

        $gologDriver->setRelation('company', $company);
        $user->setRelation('gologDriver', $gologDriver);

        return $gologDriver;
    }
}
