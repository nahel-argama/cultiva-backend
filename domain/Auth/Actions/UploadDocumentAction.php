<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\UploadDocumentDTO;
use Cultiva\Auth\Enums\DocumentValidationStatus;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\User\Jobs\VerifyUserDocumentJob;
use Cultiva\Models\User\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

class UploadDocumentAction
{
    public const CACHE_TTL_SECONDS = 86400;

    public function execute(UploadDocumentDTO $dto): User
    {
        if ($dto->user->is_verified) {
            throw new CultivaException(422, Lang::get('auth.document_verification.already_verified'));
        }

        Cache::put(
            GetDocumentValidationStatusAction::CACHE_PREFIX . $dto->user->id,
            [
                'status' => DocumentValidationStatus::PENDING->value,
                'message' => Lang::get('auth.document_verification.pending_message'),
            ],
            self::CACHE_TTL_SECONDS,
        );

        $filePath = (string) $dto->document->store('documents', 'local');
        $mimeType = (string) ($dto->document->getMimeType() ?? $dto->document->getClientMimeType());

        VerifyUserDocumentJob::dispatch($dto->user, $filePath, $mimeType);

        return $dto->user;
    }
}
