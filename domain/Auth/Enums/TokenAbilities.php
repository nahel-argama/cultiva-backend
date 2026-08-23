<?php

namespace Cultiva\Auth\Enums;

enum TokenAbilities: string
{

    case ACCESS_TOKEN = 'access-token';
    case REFRESH_TOKEN = 'refresh-token';

    public function abilities(): array
    {
        return match ($this) {
            self::ACCESS_TOKEN  => ['access'],
            self::REFRESH_TOKEN => ['refresh'],
        };
    }

    public function name(): string
    {
        return match ($this) {
            self::ACCESS_TOKEN  => 'access_token',
            self::REFRESH_TOKEN => 'refresh_token',
        };
    }
}
