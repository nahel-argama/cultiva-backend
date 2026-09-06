<?php

declare(strict_types=1);

namespace Cultiva\Auth\Strategies\SignupStrategy;

use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\Strategies\SignupStrategy\Contracts\SignupStrategyInterface;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Retailer\Actions\CreateRetailerAction;
use Cultiva\Models\User\User;
use Illuminate\Database\Eloquent\Model;

class RetailerSignupStrategy implements SignupStrategyInterface
{
    public function __construct(
        private readonly CreateRetailerAction $createRetailer,
    ) {}

    public function registerProfile(User $user, Company $company, SignUpDTO $dto): Model
    {
        $retailer = $this->createRetailer->execute($company->id, $dto->retailer);
        $retailer->setRelation('company', $company);

        return $retailer;
    }
}
