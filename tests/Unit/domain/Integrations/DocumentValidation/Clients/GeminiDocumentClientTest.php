<?php

namespace Tests\Unit\domain\Integrations\DocumentValidation\Clients;

use Cultiva\Integrations\DocumentValidation\Clients\GeminiDocumentClient;
use Gemini\Data\Blob;
use Gemini\Enums\MimeType;
use Gemini\Laravel\Facades\Gemini;
use Gemini\Responses\GenerativeModel\GenerateContentResponse;
use Tests\TestCase;

class GeminiDocumentClientTest extends TestCase
{
    public function test_should_validate_rg_image_with_gemini_and_return_structured_array(): void
    {
        // Arrange
        config(['gemini.model' => 'gemini-2.5-flash']);

        $fixture = require base_path('tests/Fixtures/Integrations/DocumentValidation/rg_valid.php');

        Gemini::fake([
            GenerateContentResponse::fake([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => $fixture],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $base64Image = base64_encode('fake-image-binary');
        $sut = $this->app->make(GeminiDocumentClient::class);

        // Action
        $result = $sut->validateRg($base64Image, 'image/jpeg');

        // Assert
        $this->assertEqualsCanonicalizing(json_decode($fixture, true), $result);

        Gemini::generativeModel('gemini-2.5-flash')->assertSent(function (string $method, array $parameters) use ($base64Image) {
            return $method === 'generateContent'
                && isset($parameters[1])
                && $parameters[1] instanceof Blob
                && $parameters[1]->mimeType === MimeType::IMAGE_JPEG
                && $parameters[1]->data === $base64Image;
        });
    }
}
