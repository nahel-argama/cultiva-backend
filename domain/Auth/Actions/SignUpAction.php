<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\Strategies\SignupStrategy\SignupStrategyFactory;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Actions\SearchCepAction;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Models\Company\Actions\CreateCompanyAction;
use Cultiva\Models\User\Actions\CreateUserAction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class SignUpAction
{
    public function __construct(
        private readonly CreateUserAction $createUser,
        private readonly CreateCompanyAction $createCompany,
        private readonly SignupStrategyFactory $strategyFactory,
        private readonly GenerateTokenPairAction $generateTokens,
        private readonly SearchCepAction $searchCep,
    ) {}

    public function execute(SignUpDTO $dto): ProfileResultDTO
    {
        $lock = Cache::lock("signup:{$dto->user->email}", 60);

        if (! $lock->get()) {
            throw new CultivaException(422, Lang::get('auth.sign_up.in_progress'));
        }

        try {
            $geo = $this->resolveGeocoding($dto);

            return $this->registerUser($dto, $geo);
        } finally {
            $lock->release();
        }
    }

    private function registerUser(
        SignUpDTO $dto,
        GeoAddressDTO $geo,
    ): ProfileResultDTO {
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

    private function resolveGeocoding(SignUpDTO $dto): GeoAddressDTO
    {
        $cep = new Cep($dto->company->address->zip);

        return $this->searchCep->execute($cep);
    }
}
