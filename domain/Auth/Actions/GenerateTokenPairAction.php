<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\AuthTokensDTO;
use Cultiva\Auth\Enums\TokenAbilities;
use Cultiva\Models\User\User;
use Illuminate\Support\Facades\DB;

class GenerateTokenPairAction
{

    public function execute(User $user): AuthTokensDTO
    {
        $accessTokenLiftime   = (int) config('auth.access_token_lifetime_minutes');
        $refreshTokenLifetime = (int) config('auth.refresh_token_lifetime_minutes');

        return DB::transaction(function () use ($user, $accessTokenLiftime, $refreshTokenLifetime) {
            $accessToken = $user->createToken(
                name: TokenAbilities::ACCESS_TOKEN->name(),
                abilities: TokenAbilities::ACCESS_TOKEN->abilities(),
                expiresAt: now()->addMinutes($accessTokenLiftime),
            );

            $refreshToken = $user->createToken(
                name: TokenAbilities::REFRESH_TOKEN->name(),
                abilities: TokenAbilities::REFRESH_TOKEN->abilities(),
                expiresAt: now()->addMinutes($refreshTokenLifetime),
            );

            return new AuthTokensDTO(
                accessToken: $accessToken->plainTextToken,
                refreshToken: $refreshToken->plainTextToken,
            );
        });
    }
}
