<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\DispatchDeliveryOrdersBatchAction;
use Illuminate\Console\Command;

class DispatchDeliveryOrdersBatchCommand extends Command
{
    protected $signature = 'delivery:dispatch-orders-batch';

    protected $description = 'Dispatch pending delivery orders in batch to GoLog TMS grouped by category';

    public function handle(DispatchDeliveryOrdersBatchAction $action): int
    {
        $this->info('Scanning open delivery orders to aggregate and dispatch in batch...');

        $result = $action->execute();

        if ($result['total_orders'] === 0) {
            $this->info('No open delivery orders found to dispatch.');

            return self::SUCCESS;
        }

        $this->info("Found {$result['total_orders']} open delivery order(s).");

        $this->line('Breakdown by Category:');
        foreach ($result['by_category'] as $category => $count) {
            $this->line("  - [{$category}]: {$count} order(s)");
        }

        $this->info("Batches sent: {$result['batches_sent']}");
        $this->info("Shipments created successfully: {$result['success_count']}");

        if ($result['error_count'] > 0) {
            $this->warn("Failed shipments: {$result['error_count']}");
            foreach ($result['errors'] as $error) {
                $this->error("  * {$error}");
            }

            return self::FAILURE;
        }

        $this->info('Batch delivery orders successfully dispatched to GoLog TMS!');

        return self::SUCCESS;
    }
}
