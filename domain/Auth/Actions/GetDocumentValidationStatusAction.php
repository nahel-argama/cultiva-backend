<?php

namespace Cultiva\Auth\Actions;

use Cultiva\Auth\DTO\DocumentValidationStatusDTO;
use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\User\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

class GetDocumentValidationStatusAction
{
    public const CACHE_PREFIX = 'document_verification:status:';

    public function execute(User $user): DocumentValidationStatusDTO
    {
        $cached = Cache::get(self::CACHE_PREFIX . $user->id);

        if (! is_array($cached)) {
            throw new CultivaException(404, Lang::get('auth.document_verification.status_not_found'));
        }

        return DocumentValidationStatusDTO::fromArray($cached);
    }
}
