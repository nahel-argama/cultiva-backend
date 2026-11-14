<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\DispatchDeliveryOrdersBatchAction;
use Illuminate\Console\Command;

class DispatchDeliveryOrdersBatchCommand extends Command
{
    protected $signature = 'delivery:dispatch-orders-batch {--date= : Target weekday date (YYYY-MM-DD) for scheduled deliveries}';

    protected $description = 'Dispatch pending delivery orders in batch to GoLog TMS grouped by category';

    public function handle(DispatchDeliveryOrdersBatchAction $action): int
    {
        $this->info('Scanning open delivery orders to aggregate and dispatch in batch...');

        /** @var ?string $date */
        $date = $this->option('date');
        $result = $action->execute($date);

        if ($result->totalOrders === 0) {
            $this->info('No open delivery orders found to dispatch.');

            return self::SUCCESS;
        }

        $this->info("Found {$result->totalOrders} open delivery order(s).");

        $this->line('Breakdown by Category:');
        foreach ($result->byCategory as $category => $count) {
            $this->line("  - [{$category}]: {$count} order(s)");
        }

        $this->info("Batches sent: {$result->batchesSent}");
        $this->info("Shipments created successfully: {$result->shipmentsCreated}");
        $this->info('Batch delivery orders successfully dispatched to GoLog TMS!');

        return self::SUCCESS;
    }
}
