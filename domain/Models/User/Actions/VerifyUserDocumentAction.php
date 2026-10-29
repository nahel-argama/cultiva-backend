<?php

namespace Cultiva\Models\User\Actions;

use Cultiva\Auth\Actions\GetDocumentValidationStatusAction;
use Cultiva\Auth\Enums\DocumentValidationStatus;
use Cultiva\Integrations\DocumentValidation\Actions\ValidateRgDocumentAction;
use Cultiva\Models\User\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Throwable;

class VerifyUserDocumentAction
{
    public const CACHE_TTL_SECONDS = 86400;

    public function __construct(
        private readonly ValidateRgDocumentAction $validateRgDocument,
    ) {}

    public function execute(User $user, string $filePath, string $mimeType): User
    {
        try {
            $contents = (string) Storage::disk('local')->get($filePath);
            $base64Content = base64_encode($contents);

            $result = $this->validateRgDocument->execute($base64Content, $mimeType);

            if ($result->isAuthenticRg()) {
                $user->update([
                    'is_verified' => true,
                ]);

                $this->cacheStatus(
                    $user,
                    DocumentValidationStatus::APPROVED,
                    Lang::get('auth.document_verification.approved_message'),
                );
            } else {
                $message = $result->confidenceReasoning !== ''
                    ? $result->confidenceReasoning
                    : Lang::get('auth.document_verification.failed_message');

                $this->cacheStatus($user, DocumentValidationStatus::FAILED, $message);
            }

            return $user;
        } catch (Throwable $exception) {
            $this->cacheStatus($user, DocumentValidationStatus::FAILED, $exception->getMessage());

            throw $exception;
        } finally {
            Storage::disk('local')->delete($filePath);
        }
    }

    private function cacheStatus(User $user, DocumentValidationStatus $status, string $message): void
    {
        Cache::put(
            GetDocumentValidationStatusAction::CACHE_PREFIX . $user->id,
            [
                'status' => $status->value,
                'message' => $message,
            ],
            self::CACHE_TTL_SECONDS,
        );
    }
}
