<?php

namespace Cultiva\Auth\DTO;

use Cultiva\Auth\Enums\DocumentValidationStatus;

final readonly class DocumentValidationStatusDTO
{
    public function __construct(
        public DocumentValidationStatus $status,
        public string $message,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status: DocumentValidationStatus::from((string) ($data['status'] ?? DocumentValidationStatus::PENDING->value)),
            message: (string) ($data['message'] ?? ''),
        );
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'message' => $this->message,
        ];
    }
}
