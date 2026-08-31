<?php

namespace Cultiva\Auth\Transformers;

use Cultiva\Auth\DTO\AuthTokensDTO;

final class AuthTokensTransformer
{
    public function transform(AuthTokensDTO $tokens): array
    {
        return [
            'access_token' => $tokens->accessToken,
            'refresh_token' => $tokens->refreshToken,
        ];
    }
}
