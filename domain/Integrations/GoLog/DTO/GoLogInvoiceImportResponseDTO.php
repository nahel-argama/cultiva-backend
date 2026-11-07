<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\DTO;

final readonly class GoLogInvoiceImportResponseDTO
{
    /**
     * @param array<int, string> $createdShipmentIds
     * @param array<int, string> $errors
     */
    public function __construct(
        public int $totalReceived,
        public int $successCount,
        public int $errorCount,
        public array $createdShipmentIds,
        public array $errors,
        public ?int $processingTimeMs = null,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            totalReceived: $data['totalReceived'],
            successCount: $data['successCount'],
            errorCount: $data['errorCount'],
            createdShipmentIds: $data['createdShipmentIds'],
            errors: $data['errors'],
            processingTimeMs: $data['processingTimeMs'] ?? null,
        );
    }
}
