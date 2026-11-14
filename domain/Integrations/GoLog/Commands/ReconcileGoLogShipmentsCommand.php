<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\ReconcileGoLogShipmentsAction;
use Illuminate\Console\Command;

class ReconcileGoLogShipmentsCommand extends Command
{
    protected $signature = 'golog:reconcile-shipments';

    protected $description = 'Reconcile non-terminal local GoLog shipments with remote GoLog TMS status';

    public function handle(ReconcileGoLogShipmentsAction $action): int
    {
        $updated = $action->execute();

        $this->info("Reconciled {$updated} GoLog shipment(s).");

        return self::SUCCESS;
    }
}
