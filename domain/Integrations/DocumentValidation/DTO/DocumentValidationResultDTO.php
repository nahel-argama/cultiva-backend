<?php

namespace Cultiva\Integrations\DocumentValidation\DTO;

readonly class DocumentValidationResultDTO
{
    public function __construct(
        public bool $isValidDoc,
        public string $docTypeDetected,
        public string $confidenceReasoning,
        public ?string $rgNumber = null,
        public ?string $cpf = null,
        public ?string $name = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            isValidDoc: (bool) ($data['is_valid_doc'] ?? false),
            docTypeDetected: (string) ($data['doc_type_detected'] ?? 'unknown'),
            confidenceReasoning: (string) ($data['confidence_reasoning'] ?? ''),
            rgNumber: $data['rg_number'] ?? null,
            cpf: $data['cpf'] ?? null,
            name: $data['name'] ?? null,
        );
    }

    public function isAuthenticRg(): bool
    {
        return $this->isValidDoc && $this->docTypeDetected === 'rg';
    }
}
