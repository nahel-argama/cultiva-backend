<?php

namespace Tests\Unit\domain\Models\User\Jobs;

use Cultiva\Models\User\Actions\VerifyUserDocumentAction;
use Cultiva\Models\User\Jobs\VerifyUserDocumentJob;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class VerifyUserDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_delegate_verification_to_verify_user_document_action(): void
    {
        // Arrange
        $user = UserFactory::new()->create([
            'is_verified' => false,
        ]);

        // Expects
        $actionMock = Mockery::mock(VerifyUserDocumentAction::class, function (MockInterface $mock) use ($user) {
            $mock->shouldReceive('execute')
                ->once()
                ->with($user, 'documents/rg.jpg', 'image/jpeg')
                ->andReturn($user);
        });

        $sut = new VerifyUserDocumentJob($user, 'documents/rg.jpg', 'image/jpeg');

        // Action
        $sut->handle($actionMock);

        // Assert
        $this->assertSame('documents/rg.jpg', $sut->filePath);
        $this->assertSame('image/jpeg', $sut->mimeType);
    }
}
