<?php

namespace Cultiva\Models\User\DTOs;

use Cultiva\Auth\Enums\ProfileType;

final class UserRegisterDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ProfileType $profileType,
    ) {}

    public static function fromArray(array $data, ProfileType $profileType): self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            profileType: $profileType,
        );
    }
}
