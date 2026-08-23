<?php

namespace Cultiva\Auth\DTO;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Producer\DTOs\ProducerRegisterDTO;
use Cultiva\Models\Retailer\DTOs\RetailerRegisterDTO;
use Cultiva\Models\User\DTOs\UserRegisterDTO;

final class SignUpDTO
{

    public function __construct(
        public readonly UserRegisterDTO      $user,
        public readonly ProfileType          $profileType,
        public readonly ?ProducerRegisterDTO $producer = null,
        public readonly ?RetailerRegisterDTO $retailer = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            user: UserRegisterDTO::fromArray($data['user']),
            profileType: ProfileType::from($data['profile_type']),
            producer: isset($data['producer']) ? ProducerRegisterDTO::fromArray($data['producer']) : null,
            retailer: isset($data['retailer']) ? RetailerRegisterDTO::fromArray($data['retailer']) : null,
        );
    }
}
