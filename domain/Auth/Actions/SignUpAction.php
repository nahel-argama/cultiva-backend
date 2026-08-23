<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\SignUpDTO;
use Cultiva\Auth\DTO\SignUpResultDTO;
use Cultiva\Models\Producer\Actions\CreateProducerAction;
use Cultiva\Models\Retailer\Actions\CreateRetailerAction;
use Cultiva\Models\User\Actions\CreateUserAction;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;

class SignUpAction
{

    public function __construct(
        private readonly CreateUserAction        $createUser,
        private readonly CreateProducerAction    $createProducer,
        private readonly CreateRetailerAction    $createRetailer,
        private readonly GenerateTokenPairAction $generateTokens,
    ) {}

    public function execute(SignUpDTO $dto): SignUpResultDTO
    {
        if ($dto->producer === null && $dto->retailer === null) {
            throw new DomainException(Lang::get('auth.sign_up.user_without_profile'));
        }

        return DB::transaction(function () use ($dto) {
            $user = $this->createUser->execute($dto->user);

            $producer = null;
            if ($dto->producer !== null) {
                $producer = $this->createProducer->execute($user->id, $dto->producer);
            }

            $retailer = null;
            if ($dto->retailer !== null) {
                $retailer = $this->createRetailer->execute($user->id, $dto->retailer);
            }

            $tokens = $this->generateTokens->execute($user);

            return new SignUpResultDTO(
                user: $user,
                profileType: $dto->profileType,
                producer: $producer,
                retailer: $retailer,
                tokens: $tokens,
            );
        });
    }
}
