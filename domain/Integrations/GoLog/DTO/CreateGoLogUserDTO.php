<?php

namespace Cultiva\Integrations\GoLog\DTO;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Company\DTOs\CompanyRegisterDTO;
use Cultiva\Models\User\DTOs\UserRegisterDTO;

final readonly class CreateGoLogUserDTO
{
    public function __construct(
        public UserRegisterDTO $user,
        public CompanyRegisterDTO $company,
        public string $cpf,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            user: UserRegisterDTO::fromArray($data['user'], ProfileType::GOLOG_DRIVER),
            company: CompanyRegisterDTO::fromArray($data['company']),
            cpf: $data['user']['cpf'],
        );
    }
}
