<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Models\User\User;

class GetAuthenticatedProfileAction
{
    public function execute(User $user): ProfileResultDTO
    {
        return new ProfileResultDTO(
            user: $user,
        );
    }
}
