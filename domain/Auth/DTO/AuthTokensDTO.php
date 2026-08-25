<?php

namespace Cultiva\Auth\DTO;

final class AuthTokensDTO
{

    public function __construct(
        public readonly string $accessToken,
        public readonly string $refreshToken,
    ) {}
}
