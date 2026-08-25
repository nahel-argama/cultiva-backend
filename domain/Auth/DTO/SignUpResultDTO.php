<?php

namespace Cultiva\Auth\DTO;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Producer\Producer;
use Cultiva\Models\Retailer\Retailer;
use Cultiva\Models\User\User;

final class SignUpResultDTO
{

    public function __construct(
        public readonly User          $user,
        public readonly ProfileType   $profileType,
        public readonly ?Producer     $producer,
        public readonly ?Retailer     $retailer,
        public readonly AuthTokensDTO $tokens,
    ) {}
}
