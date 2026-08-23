<?php

namespace Cultiva\Models\User\Actions;

use Cultiva\Models\User\DTOs\UserRegisterDTO;
use Cultiva\Models\User\User;

final class CreateUserAction
{

    public function execute(UserRegisterDTO $dto): User
    {
        return User::query()->create([
            'name'     => $dto->name,
            'email'    => $dto->email,
            'password' => $dto->password,
        ]);
    }
}
