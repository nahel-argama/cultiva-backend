<?php

namespace Cultiva\Models\User\Transformers;

use Cultiva\Models\User\User;

final class UserTransformer
{
    public function transform(User $user): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'is_verified' => (bool) $user->is_verified,
        ];
    }
}
