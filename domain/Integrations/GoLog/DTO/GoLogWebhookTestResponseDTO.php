<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class GoLogWebhookTestResponseDTO
{
    public function __construct(
        public bool $success,
        public int $statusCode,
        public ?string $responseBody,
        public ?string $error,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self(
            success: (bool) ($data['success'] ?? false),
            statusCode: (int) ($data['statusCode'] ?? 0),
            responseBody: is_string($data['responseBody'] ?? null) ? $data['responseBody'] : null,
            error: is_string($data['error'] ?? null) ? $data['error'] : null,
        );
    }
}
