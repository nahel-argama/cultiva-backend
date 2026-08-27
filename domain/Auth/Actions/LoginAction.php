<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\LoginDTO;
use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Models\User\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;

class LoginAction
{
    public function __construct(
        private readonly GenerateTokenPairAction $generateTokens,
    ) {}

    public function execute(LoginDTO $dto): ProfileResultDTO
    {
        $user = User::query()
            ->where('email', $dto->email)
            ->first();

        if ($user === null || ! Hash::check($dto->password, $user->password)) {
            throw new DomainException(Lang::get('auth.login.invalid_credentials'), 401);
        }

        if (! $user->is_active) {
            throw new DomainException(Lang::get('auth.login.user_not_active'), 403);
        }

        return DB::transaction(function () use ($user) {
            $tokens = $this->generateTokens->execute($user);

            $user->update([
                'last_login' => now(),
            ]);

            return new ProfileResultDTO(
                user: $user,
                profileType: $user->getProfileType(),
                producer: $user->producer,
                retailer: $user->retailer,
                tokens: $tokens,
            );
        });
    }
}
