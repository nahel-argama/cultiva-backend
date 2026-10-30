<?php

namespace Cultiva\Models\Offer\Jobs;

use Cultiva\Models\Offer\Actions\PopulateProductImageAction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PopulateProductImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly string $sourceProductId,
        public readonly ?string $productName = null,
    ) {}

    public function handle(PopulateProductImageAction $action): void
    {
        $action->execute($this->sourceProductId, $this->productName);
    }
}
