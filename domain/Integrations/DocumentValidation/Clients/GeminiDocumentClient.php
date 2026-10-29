<?php

namespace Cultiva\Integrations\DocumentValidation\Clients;

use Cultiva\Integrations\DocumentValidation\Config;
use Gemini\Data\Blob;
use Gemini\Data\GenerationConfig;
use Gemini\Data\Schema;
use Gemini\Enums\DataType;
use Gemini\Enums\MimeType;
use Gemini\Enums\ResponseMimeType;
use Gemini\Laravel\Facades\Gemini;

class GeminiDocumentClient
{
    private const string PROMPT = <<<'PROMPT'
Examine a imagem enviada. Ela deve ser uma Carteira de Identidade brasileira (RG / CIN).
1. Identifique se é um 'rg' ou 'unknown'.
2. Verifique se visualmente parece um documento de identidade (RG) autêntico e legível.
3. Extraia os dados quando visíveis: número do RG, CPF e nome completo do titular.
PROMPT;

    public function __construct(
        private readonly Config $config,
    ) {}

    public function validateRg(string $base64Content, string $mimeType): array
    {
        $resolvedMimeType = match ($mimeType) {
            'image/jpg' => MimeType::IMAGE_JPEG,
            default => MimeType::from($mimeType),
        };

        $generationConfig = new GenerationConfig(
            temperature: 0.0,
            responseMimeType: ResponseMimeType::APPLICATION_JSON,
            responseSchema: new Schema(
                type: DataType::OBJECT,
                properties: [
                    'is_valid_doc' => new Schema(
                        type: DataType::BOOLEAN,
                        description: 'True se parecer um documento RG autêntico e legível.',
                    ),
                    'doc_type_detected' => new Schema(
                        type: DataType::STRING,
                        description: "Tipo identificado: 'rg' ou 'unknown'.",
                        enum: ['rg', 'unknown'],
                    ),
                    'confidence_reasoning' => new Schema(
                        type: DataType::STRING,
                        description: 'Explicação do porquê o documento foi aceito ou rejeitado.',
                    ),
                    'rg_number' => new Schema(
                        type: DataType::STRING,
                        description: 'Número do RG extraído.',
                        nullable: true,
                    ),
                    'cpf' => new Schema(
                        type: DataType::STRING,
                        description: 'CPF extraído, caso conste no RG.',
                        nullable: true,
                    ),
                    'name' => new Schema(
                        type: DataType::STRING,
                        description: 'Nome completo do titular do RG.',
                        nullable: true,
                    ),
                ],
                required: ['is_valid_doc', 'doc_type_detected', 'confidence_reasoning'],
            ),
        );

        $response = Gemini::generativeModel(model: $this->config->getModel())
            ->withGenerationConfig($generationConfig)
            ->generateContent(
                self::PROMPT,
                new Blob(
                    mimeType: $resolvedMimeType,
                    data: $base64Content,
                ),
            );

        return (array) $response->json(associative: true);
    }
}
