<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

use Cultiva\Integrations\GoLog\Enums\GoLogUserProfile;

final readonly class GoLogUserResponseDTO
{
    public function __construct(
        public string $id,
        public string $name,
        public string $email,
        public string $cpf,
        public GoLogUserProfile $userProfile,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            email: $data['email'],
            cpf: $data['cpf'],
            userProfile: GoLogUserProfile::from($data['userProfile']),
        );
    }
}
