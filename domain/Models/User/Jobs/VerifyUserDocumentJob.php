<?php

namespace Cultiva\Models\User\Jobs;

use Cultiva\Models\User\Actions\VerifyUserDocumentAction;
use Cultiva\Models\User\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class VerifyUserDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $filePath,
        public readonly string $mimeType,
    ) {}

    public function handle(VerifyUserDocumentAction $action): void
    {
        $action->execute($this->user, $this->filePath, $this->mimeType);
    }
}
