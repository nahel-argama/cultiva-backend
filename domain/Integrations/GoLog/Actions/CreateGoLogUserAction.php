<?php

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Auth\Actions\GenerateTokenPairAction;
use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Actions\SearchCepAction;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\DTO\CreateGoLogUserDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogUserProfile;
use Cultiva\Models\Company\Actions\CreateCompanyAction;
use Cultiva\Models\Company\Company;
use Cultiva\Models\Delivery\GoLogDriver;
use Cultiva\Models\User\Actions\CreateUserAction;
use Cultiva\Models\User\User;
use Illuminate\Support\Facades\DB;

class CreateGoLogUserAction
{
    public function __construct(
        private readonly CreateUserAction $createUser,
        private readonly CreateCompanyAction $createCompany,
        private readonly SearchCepAction $searchCep,
        private readonly GoLogClient $goLogClient,
        private readonly GenerateTokenPairAction $generateTokens,
    ) {}

    public function execute(CreateGoLogUserDTO $dto): ProfileResultDTO
    {
        $this->validateBusinessRules($dto);

        $geo = $this->searchCep->execute(new Cep($dto->company->address->zip));

        $goLogUser = $this->goLogClient->createUser(
            name: $dto->user->name,
            email: $dto->user->email,
            password: $dto->user->password,
            cpf: $dto->cpf,
            profile: GoLogUserProfile::DRIVER,
        );

        return DB::transaction(function () use ($dto, $geo, $goLogUser): ProfileResultDTO {
            $user = $this->createUser->execute($dto->user);
            $company = $this->createCompany->execute($user->id, $dto->company, $geo);

            GoLogDriver::query()->create([
                'company_id' => $company->id,
                'cpf' => $dto->cpf,
                'golog_user_id' => $goLogUser->id,
            ]);

            $tokens = $this->generateTokens->execute($user);

            return new ProfileResultDTO(
                user: $user,
                tokens: $tokens,
            );
        });
    }

    private function validateBusinessRules(CreateGoLogUserDTO $dto): void
    {
        if (User::query()->where('email', $dto->user->email)->exists()) {
            throw new CultivaException(409, 'User with this email already exists.');
        }

        if (GoLogDriver::query()->where('cpf', $dto->cpf)->exists()) {
            throw new CultivaException(409, 'Driver with this CPF already exists.');
        }

        if (Company::query()->where('document_number', $dto->company->documentNumber)->exists()) {
            throw new CultivaException(409, 'Company with this document number already exists.');
        }
    }
}
