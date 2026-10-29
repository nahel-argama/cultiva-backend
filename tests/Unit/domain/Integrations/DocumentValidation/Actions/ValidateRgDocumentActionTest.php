<?php

namespace Tests\Unit\domain\Integrations\DocumentValidation\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\DocumentValidation\Actions\ValidateRgDocumentAction;
use Cultiva\Integrations\DocumentValidation\Clients\GeminiDocumentClient;
use Cultiva\Integrations\DocumentValidation\DTO\DocumentValidationResultDTO;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ValidateRgDocumentActionTest extends TestCase
{
    public function test_should_return_valid_dto_when_gemini_confirms_authentic_rg(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/DocumentValidation/rg_valid.php');
        $payload = json_decode($fixture, true);
        $base64 = base64_encode('fake-rg-bytes');

        // Expects
        $clientMock = Mockery::mock(GeminiDocumentClient::class, function (MockInterface $mock) use ($base64, $payload) {
            $mock->shouldReceive('validateRg')
                ->once()
                ->with($base64, 'image/png')
                ->andReturn($payload);
        });

        $sut = new ValidateRgDocumentAction($clientMock);

        // Action
        $result = $sut->execute($base64, 'image/png');

        // Assert
        $this->assertInstanceOf(DocumentValidationResultDTO::class, $result);
        $this->assertTrue($result->isValidDoc);
        $this->assertSame('rg', $result->docTypeDetected);
        $this->assertTrue($result->isAuthenticRg());
        $this->assertSame('123456789', $result->rgNumber);
        $this->assertSame('12345678909', $result->cpf);
        $this->assertSame('Maria Silva', $result->name);
    }

    public function test_should_return_non_authentic_dto_when_document_is_invalid(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/DocumentValidation/rg_invalid.php');
        $payload = json_decode($fixture, true);
        $base64 = base64_encode('fake-invalid-bytes');

        // Expects
        $clientMock = Mockery::mock(GeminiDocumentClient::class, function (MockInterface $mock) use ($base64, $payload) {
            $mock->shouldReceive('validateRg')
                ->once()
                ->with($base64, 'image/jpeg')
                ->andReturn($payload);
        });

        $sut = new ValidateRgDocumentAction($clientMock);

        // Action
        $result = $sut->execute($base64, 'image/jpeg');

        // Assert
        $this->assertInstanceOf(DocumentValidationResultDTO::class, $result);
        $this->assertFalse($result->isValidDoc);
        $this->assertSame('unknown', $result->docTypeDetected);
        $this->assertFalse($result->isAuthenticRg());
    }

    public function test_should_throw_503_cultiva_exception_when_client_throws(): void
    {
        // Arrange
        $base64 = base64_encode('fake-rg-bytes');

        // Expects
        $clientMock = Mockery::mock(GeminiDocumentClient::class, function (MockInterface $mock) use ($base64) {
            $mock->shouldReceive('validateRg')
                ->once()
                ->with($base64, 'image/jpeg')
                ->andThrow(new RuntimeException('Gemini timeout'));
        });

        $sut = new ValidateRgDocumentAction($clientMock);

        // Action & Assert
        try {
            $sut->execute($base64, 'image/jpeg');
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
            $this->assertSame('Document validation service unavailable.', $exception->getMessage());
        }
    }
}
