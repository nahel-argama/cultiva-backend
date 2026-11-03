<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

use Cultiva\Integrations\GoLog\Enums\GoLogUserProfile;

final readonly class GoLogAuthResponseDTO
{
    public function __construct(
        public string $token,
        public string $userId,
        public string $userName,
        public GoLogUserProfile $userRole,
        public string $companyId,
        public string $companyName,
        public string $companyType,
        public bool $isMaster,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'],
            userId: $data['userId'],
            userName: $data['userName'],
            userRole: GoLogUserProfile::from($data['userRole']),
            companyId: $data['companyId'],
            companyName: $data['companyName'],
            companyType: $data['companyType'],
            isMaster: $data['isMaster'],
        );
    }
}
