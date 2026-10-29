<?php

namespace Cultiva\Models\User\Actions;

use Cultiva\Auth\Actions\GetDocumentValidationStatusAction;
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

                Cache::put(
                    GetDocumentValidationStatusAction::CACHE_PREFIX . $user->id,
                    [
                        'status' => 'approved',
                        'message' => Lang::get('auth.document_verification.approved_message'),
                    ],
                    self::CACHE_TTL_SECONDS,
                );
            } else {
                $message = $result->confidenceReasoning !== ''
                    ? $result->confidenceReasoning
                    : Lang::get('auth.document_verification.failed_message');

                Cache::put(
                    GetDocumentValidationStatusAction::CACHE_PREFIX . $user->id,
                    [
                        'status' => 'failed',
                        'message' => $message,
                    ],
                    self::CACHE_TTL_SECONDS,
                );
            }

            return $user;
        } catch (Throwable $exception) {
            Cache::put(
                GetDocumentValidationStatusAction::CACHE_PREFIX . $user->id,
                [
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                ],
                self::CACHE_TTL_SECONDS,
            );

            throw $exception;
        } finally {
            Storage::disk('local')->delete($filePath);
        }
    }
}
