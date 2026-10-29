<?php

namespace Cultiva\Integrations\DocumentValidation\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\DocumentValidation\Clients\GeminiDocumentClient;
use Cultiva\Integrations\DocumentValidation\DTO\DocumentValidationResultDTO;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Throwable;

class ValidateRgDocumentAction
{
    public function __construct(
        private readonly GeminiDocumentClient $client,
    ) {}

    public function execute(string $base64Content, string $mimeType): DocumentValidationResultDTO
    {
        try {
            $payload = $this->client->validateRg($base64Content, $mimeType);

            Log::info('RG document validation payload received', [
                'payload' => $payload,
            ]);
        } catch (Throwable $exception) {
            throw new CultivaException(
                503,
                Lang::get('integrations.document_validation.unavailable'),
                $exception,
            );
        }

        return DocumentValidationResultDTO::from($payload);
    }
}
