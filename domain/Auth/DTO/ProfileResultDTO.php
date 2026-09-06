<?php

namespace Cultiva\Auth\DTO;

use Cultiva\Models\User\User;

final class ProfileResultDTO
{
    public function __construct(
        public readonly User $user,
        public readonly ?AuthTokensDTO $tokens = null,
    ) {}
}
