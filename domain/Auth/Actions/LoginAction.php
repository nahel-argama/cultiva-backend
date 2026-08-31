<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\LoginDTO;
use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\User\User;
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
            throw new CultivaException(401, Lang::get('auth.login.invalid_credentials'));
        }

        if (! $user->is_active) {
            throw new CultivaException(403, Lang::get('auth.login.user_not_active'));
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
