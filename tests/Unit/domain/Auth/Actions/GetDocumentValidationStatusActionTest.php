<?php

namespace Tests\Unit\domain\Auth\Actions;

use Cultiva\Auth\Actions\GetDocumentValidationStatusAction;
use Cultiva\Auth\Enums\DocumentValidationStatus;
use Cultiva\Base\Exceptions\CultivaException;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class GetDocumentValidationStatusActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_return_status_dto_when_cache_entry_exists(): void
    {
        // Arrange
        $user = UserFactory::new()->create();

        Cache::put("document_verification:status:{$user->id}", [
            'status' => 'pending',
            'message' => 'Document verification is in progress.',
        ], now()->addDay());

        $sut = new GetDocumentValidationStatusAction;

        // Action
        $result = $sut->execute($user);

        // Assert
        $this->assertSame(DocumentValidationStatus::PENDING, $result->status);
        $this->assertSame('pending', $result->status->value);
        $this->assertSame('Document verification is in progress.', $result->message);
    }

    public function test_should_throw_404_cultiva_exception_when_no_cache_entry_exists(): void
    {
        // Arrange
        $user = UserFactory::new()->create();
        Cache::forget("document_verification:status:{$user->id}");

        $sut = new GetDocumentValidationStatusAction;

        // Action & Assert
        try {
            $sut->execute($user);
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
            $this->assertSame(Lang::get('auth.document_verification.status_not_found'), $exception->getMessage());
        }
    }
}
