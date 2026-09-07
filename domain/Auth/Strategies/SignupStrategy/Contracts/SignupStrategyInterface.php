<?php

declare(strict_types=1);

namespace Cultiva\Auth\Strategies\SignupStrategy\Contracts;

use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Models\Company\Company;
use Cultiva\Models\User\User;
use Illuminate\Database\Eloquent\Model;

interface SignupStrategyInterface
{
    public function registerProfile(User $user, Company $company, SignUpDTO $dto): Model;
}
