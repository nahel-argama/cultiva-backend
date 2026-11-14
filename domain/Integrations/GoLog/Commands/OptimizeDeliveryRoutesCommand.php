<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\OptimizeDeliveryRoutesAction;
use Illuminate\Console\Command;

class OptimizeDeliveryRoutesCommand extends Command
{
    protected $signature = 'delivery:optimize-routes';

    protected $description = 'Optimize pending GoLog shipments using provisioned driver work schedules in GoLog TMS';

    public function handle(OptimizeDeliveryRoutesAction $action): int
    {
        $this->info('Scanning pending GoLog shipments for route optimization...');

        $result = $action->execute();

        if ($result->shipmentsOptimized === 0 && $result->skippedShipmentIds === []) {
            $this->info('No pending GoLog shipments found to optimize.');

            return self::SUCCESS;
        }

        $this->info("Routes created: {$result->routesCount}");
        $this->info("Shipments optimized: {$result->shipmentsOptimized}");

        if ($result->skippedShipmentIds !== []) {
            $skippedCount = count($result->skippedShipmentIds);
            $this->warn("Shipments skipped (remained PENDENTE): {$skippedCount}");
        }

        $this->info("Driver work schedules used: {$result->workSchedulesUsed}");
        $this->info('Routes optimized successfully in GoLog TMS!');

        return self::SUCCESS;
    }
}
