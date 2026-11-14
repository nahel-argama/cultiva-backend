<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Jobs;

use Cultiva\Integrations\GoLog\Actions\HandleGoLogWebhookAction;
use Cultiva\Models\DeliveryOrder\GoLogWebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessGoLogWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $webhookEventId,
    ) {}

    public function handle(HandleGoLogWebhookAction $action): void
    {
        $event = GoLogWebhookEvent::find($this->webhookEventId);

        if (! $event instanceof GoLogWebhookEvent || $event->processed_at !== null) {
            return;
        }

        $action->execute($event);
    }
}
