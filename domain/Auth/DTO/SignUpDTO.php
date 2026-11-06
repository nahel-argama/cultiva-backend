<?php

namespace Cultiva\Auth\DTO;

use Cultiva\Auth\Enums\ProfileType;
use Cultiva\Models\Company\DTOs\CompanyRegisterDTO;
use Cultiva\Models\Delivery\DTOs\DeliveryRegisterDTO;
use Cultiva\Models\Producer\DTOs\ProducerRegisterDTO;
use Cultiva\Models\Retailer\DTOs\RetailerRegisterDTO;
use Cultiva\Models\User\DTOs\UserRegisterDTO;

final class SignUpDTO
{
    public function __construct(
        public readonly UserRegisterDTO      $user,
        public readonly ProfileType          $profileType,
        public readonly CompanyRegisterDTO   $company,
        public readonly ?ProducerRegisterDTO $producer = null,
        public readonly ?RetailerRegisterDTO $retailer = null,
        public readonly ?DeliveryRegisterDTO $delivery = null,
    ) {}

    public static function fromArray(array $data): self
    {
        $profileType = ProfileType::from($data['profile_type']);

        return new self(
            user: UserRegisterDTO::fromArray($data['user'], $profileType),
            profileType: $profileType,
            company: CompanyRegisterDTO::fromArray($data['company']),
            producer: $profileType === ProfileType::PRODUCER ? ProducerRegisterDTO::fromArray($data['producer'] ?? []) : null,
            retailer: $profileType === ProfileType::RETAILER ? RetailerRegisterDTO::fromArray($data['retailer'] ?? []) : null,
            delivery: $profileType === ProfileType::DELIVERY ? DeliveryRegisterDTO::fromArray($data['delivery'] ?? []) : null,
        );
    }
}
