<?php

namespace Cultiva\Models\User\Actions;

use Cultiva\Integrations\DocumentValidation\Actions\ValidateRgDocumentAction;
use Cultiva\Models\User\User;
use Illuminate\Support\Facades\Storage;

class VerifyUserDocumentAction
{
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
            }

            return $user;
        } finally {
            Storage::disk('local')->delete($filePath);
        }
    }
}
