<?php

namespace Cultiva\Auth\DTO;

final readonly class DocumentValidationStatusDTO
{
    public function __construct(
        public string $status,
        public string $message,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status: (string) ($data['status'] ?? 'pending'),
            message: (string) ($data['message'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
        ];
    }
}
