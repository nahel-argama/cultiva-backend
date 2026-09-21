<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\ProfileResultDTO;
use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\Facades\SignUpFacade;
use Cultiva\Base\Exceptions\CultivaException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

class SignUpAction
{
    public function __construct(
        private readonly SignUpFacade $signUpFacade,
    ) {}

    public function execute(SignUpDTO $dto): ProfileResultDTO
    {
        $lock = Cache::lock("signup:{$dto->user->email}", 60);

        if (! $lock->get()) {
            throw new CultivaException(422, Lang::get('auth.sign_up.in_progress'));
        }

        try {
            return $this->signUpFacade->register($dto);
        } finally {
            $lock->release();
        }
    }

}
