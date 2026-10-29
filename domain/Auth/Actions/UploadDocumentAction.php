<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\UploadDocumentDTO;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\User\Jobs\VerifyUserDocumentJob;
use Cultiva\Models\User\User;
use Illuminate\Support\Facades\Lang;

class UploadDocumentAction
{
    public function execute(UploadDocumentDTO $dto): User
    {
        if ($dto->user->is_verified) {
            throw new CultivaException(422, Lang::get('auth.document_verification.already_verified'));
        }

        $filePath = (string) $dto->document->store('documents', 'local');
        $mimeType = (string) ($dto->document->getMimeType() ?? $dto->document->getClientMimeType());

        VerifyUserDocumentJob::dispatch($dto->user, $filePath, $mimeType);

        return $dto->user;
    }
}
