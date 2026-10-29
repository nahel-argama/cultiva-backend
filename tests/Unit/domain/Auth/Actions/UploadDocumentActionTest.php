<?php

namespace Tests\Unit\domain\Auth\Actions;

use Cultiva\Auth\Actions\UploadDocumentAction;
use Cultiva\Auth\DTO\UploadDocumentDTO;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\User\Jobs\VerifyUserDocumentJob;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadDocumentActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_store_document_on_local_disk_and_dispatch_verification_job(): void
    {
        // Arrange
        Storage::fake('local');
        Bus::fake([VerifyUserDocumentJob::class]);
        Cache::flush();

        $user = UserFactory::new()->create([
            'is_verified' => false,
        ]);

        $file = UploadedFile::fake()->image('rg.jpg');
        $dto = new UploadDocumentDTO(
            user: $user,
            document: $file,
        );

        $sut = new UploadDocumentAction;

        // Action
        $result = $sut->execute($dto);

        // Assert
        $this->assertTrue($result->is($user));

        $cachedStatus = Cache::get("document_verification:status:{$user->id}");
        $this->assertNotNull($cachedStatus);
        $this->assertSame('pending', $cachedStatus['status']);
        $this->assertSame(Lang::get('auth.document_verification.pending_message'), $cachedStatus['message']);

        Bus::assertDispatched(VerifyUserDocumentJob::class, function (VerifyUserDocumentJob $job) use ($user) {
            Storage::disk('local')->assertExists($job->filePath);

            return $job->user->is($user)
                && str_starts_with($job->filePath, 'documents/')
                && $job->mimeType === 'image/jpeg';
        });
    }

    public function test_should_throw_422_when_user_is_already_verified(): void
    {
        // Arrange
        Storage::fake('local');
        Bus::fake([VerifyUserDocumentJob::class]);
        Cache::flush();

        $user = UserFactory::new()->create([
            'is_verified' => true,
        ]);

        $file = UploadedFile::fake()->image('rg.jpg');
        $dto = new UploadDocumentDTO(
            user: $user,
            document: $file,
        );

        $sut = new UploadDocumentAction;

        // Action & Assert
        try {
            $sut->execute($dto);
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            $this->assertSame(Lang::get('auth.document_verification.already_verified'), $exception->getMessage());
        }

        Bus::assertNotDispatched(VerifyUserDocumentJob::class);
    }
}
