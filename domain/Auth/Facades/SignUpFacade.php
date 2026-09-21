<?php

namespace Cultiva\Auth\Facades;

use Cultiva\Auth\Actions\GenerateTokenPairAction;
use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\Strategies\SignupStrategy\SignupStrategyFactory;
use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Actions\SearchCepAction;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Models\Company\Actions\CreateCompanyAction;
use Cultiva\Models\User\Actions\CreateUserAction;
use Illuminate\Support\Facades\DB;

final class SignUpFacade
{
    public function __construct(
        private readonly CreateUserAction $createUser,
        private readonly CreateCompanyAction $createCompany,
        private readonly SignupStrategyFactory $strategyFactory,
        private readonly GenerateTokenPairAction $generateTokens,
        private readonly SearchCepAction $searchCep,
    ) {}

    public function register(SignUpDTO $dto): ProfileResultDTO
    {
        $cep = new Cep($dto->company->address->zip);
        $geo = $this->searchCep->execute($cep);

        return DB::transaction(function () use ($dto, $geo) {
            $user = $this->createUser->execute($dto->user);
            $company = $this->createCompany->execute($user->id, $dto->company, $geo);

            $strategy = $this->strategyFactory->make($dto->profileType);
            $strategy->registerProfile($user, $company, $dto);

            $tokens = $this->generateTokens->execute($user);

            return new ProfileResultDTO(
                user: $user,
                tokens: $tokens,
            );
        });
    }
}
