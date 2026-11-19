<?php

namespace Cultiva\Integrations\GoLog\Jobs;

use Cultiva\Integrations\GoLog\Actions\DispatchDeliveryOrdersBatchAction;
use Cultiva\Integrations\GoLog\Actions\OptimizeDeliveryRoutesAction;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use DateTimeInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class DispatchPurchasedDeliveryOrdersJob implements ShouldQueue
{
    use Queueable;

    private const int ORDERS_PER_OPTIMIZATION = 20;

    public int $backoff = 30;

    public int $maxExceptions = 5;

    public function middleware(): array
    {
        return [(new WithoutOverlapping)->releaseAfter(10)->expireAfter(120)];
    }

    public function retryUntil(): DateTimeInterface
    {
        return now()->addMinutes(30);
    }

    public function handle(DispatchDeliveryOrdersBatchAction $dispatch, OptimizeDeliveryRoutesAction $optimize): void
    {
        $dispatch->execute();

        $pendingOrderCount = DeliveryOrder::query()
            ->whereHas('gologShipments', fn ($query) => $query->where('status', GoLogShipmentStatus::PENDENTE))
            ->limit(self::ORDERS_PER_OPTIMIZATION)
            ->pluck('id')
            ->count();

        if ($pendingOrderCount >= self::ORDERS_PER_OPTIMIZATION) {
            $optimize->execute();
        }
    }
}
