<?php

namespace Cultiva\Auth\DTO;

use Cultiva\Models\User\User;
use Illuminate\Http\UploadedFile;

final readonly class UploadDocumentDTO
{
    public function __construct(
        public User $user,
        public UploadedFile $document,
    ) {}

    public static function from(User $user, array $data): self
    {
        return new self(
            user: $user,
            document: $data['document'],
        );
    }
}
