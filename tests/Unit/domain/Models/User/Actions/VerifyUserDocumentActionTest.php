<?php

namespace Tests\Unit\domain\Models\User\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\DocumentValidation\Actions\ValidateRgDocumentAction;
use Cultiva\Integrations\DocumentValidation\DTO\DocumentValidationResultDTO;
use Cultiva\Models\User\Actions\VerifyUserDocumentAction;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class VerifyUserDocumentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_mark_user_as_verified_and_delete_temp_file_when_rg_is_authentic(): void
    {
        // Arrange
        Storage::fake('local');
        Storage::disk('local')->put('documents/rg.jpg', 'fake-rg-content');

        $user = UserFactory::new()->create([
            'is_verified' => false,
        ]);

        $validationResult = new DocumentValidationResultDTO(
            isValidDoc: true,
            docTypeDetected: 'rg',
            confidenceReasoning: 'Authentic RG.',
            rgNumber: '123456789',
            cpf: '12345678909',
            name: $user->name,
        );

        // Expects
        $validateActionMock = Mockery::mock(ValidateRgDocumentAction::class, function (MockInterface $mock) use ($validationResult) {
            $mock->shouldReceive('execute')
                ->once()
                ->with(base64_encode('fake-rg-content'), 'image/jpeg')
                ->andReturn($validationResult);
        });

        $sut = new VerifyUserDocumentAction($validateActionMock);

        // Action
        $result = $sut->execute($user, 'documents/rg.jpg', 'image/jpeg');

        // Assert
        $this->assertTrue($result->is_verified);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_verified' => true,
        ]);
        Storage::disk('local')->assertMissing('documents/rg.jpg');

        $cachedStatus = Cache::get("document_verification:status:{$user->id}");
        $this->assertNotNull($cachedStatus);
        $this->assertSame('approved', $cachedStatus['status']);
        $this->assertSame(Lang::get('auth.document_verification.approved_message'), $cachedStatus['message']);
    }

    public function test_should_keep_user_unverified_and_delete_temp_file_when_rg_is_invalid(): void
    {
        // Arrange
        Storage::fake('local');
        Storage::disk('local')->put('documents/invalid.png', 'fake-invalid-content');

        $user = UserFactory::new()->create([
            'is_verified' => false,
        ]);

        $validationResult = new DocumentValidationResultDTO(
            isValidDoc: false,
            docTypeDetected: 'unknown',
            confidenceReasoning: 'Not an RG.',
        );

        // Expects
        $validateActionMock = Mockery::mock(ValidateRgDocumentAction::class, function (MockInterface $mock) use ($validationResult) {
            $mock->shouldReceive('execute')
                ->once()
                ->with(base64_encode('fake-invalid-content'), 'image/png')
                ->andReturn($validationResult);
        });

        $sut = new VerifyUserDocumentAction($validateActionMock);

        // Action
        $result = $sut->execute($user, 'documents/invalid.png', 'image/png');

        // Assert
        $this->assertFalse($result->is_verified);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_verified' => false,
        ]);
        Storage::disk('local')->assertMissing('documents/invalid.png');

        $cachedStatus = Cache::get("document_verification:status:{$user->id}");
        $this->assertNotNull($cachedStatus);
        $this->assertSame('failed', $cachedStatus['status']);
        $this->assertSame('Not an RG.', $cachedStatus['message']);
    }

    public function test_should_delete_temp_file_even_when_validation_throws_exception(): void
    {
        // Arrange
        Storage::fake('local');
        Storage::disk('local')->put('documents/error.jpg', 'fake-error-content');

        $user = UserFactory::new()->create([
            'is_verified' => false,
        ]);

        // Expects
        $validateActionMock = Mockery::mock(ValidateRgDocumentAction::class, function (MockInterface $mock) {
            $mock->shouldReceive('execute')
                ->once()
                ->with(base64_encode('fake-error-content'), 'image/jpeg')
                ->andThrow(new CultivaException(503, 'Document validation service unavailable.'));
        });

        $sut = new VerifyUserDocumentAction($validateActionMock);

        // Action & Assert
        try {
            $sut->execute($user, 'documents/error.jpg', 'image/jpeg');
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
            Storage::disk('local')->assertMissing('documents/error.jpg');

            $cachedStatus = Cache::get("document_verification:status:{$user->id}");
            $this->assertNotNull($cachedStatus);
            $this->assertSame('failed', $cachedStatus['status']);
            $this->assertSame('Document validation service unavailable.', $cachedStatus['message']);
        }
    }
}
