<?php

namespace Tests\Unit\domain\Integrations\GoLog\Jobs;

use Cultiva\Integrations\GoLog\Actions\DispatchDeliveryOrdersBatchAction;
use Cultiva\Integrations\GoLog\Actions\OptimizeDeliveryRoutesAction;
use Cultiva\Integrations\GoLog\DTO\DispatchBatchResultDTO;
use Cultiva\Integrations\GoLog\DTO\OptimizeRoutesResultDTO;
use Cultiva\Integrations\GoLog\Enums\GoLogOperationType;
use Cultiva\Integrations\GoLog\Enums\GoLogShipmentStatus;
use Cultiva\Integrations\GoLog\Jobs\DispatchPurchasedDeliveryOrdersJob;
use Cultiva\Models\DeliveryOrder\DeliveryOrder;
use Database\Factories\DeliveryOrderFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchPurchasedDeliveryOrdersJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_optimize_after_twenty_distinct_imported_orders(): void
    {
        $orders = DeliveryOrderFactory::new()->count(20)->create();
        $dispatch = $this->mock(DispatchDeliveryOrdersBatchAction::class);
        $optimize = $this->mock(OptimizeDeliveryRoutesAction::class);
        $dispatch->shouldReceive('execute')->once()->andReturnUsing(function () use ($orders) {
            foreach ($orders->take(19) as $order) {
                $this->recordShipments($order);
            }

            return new DispatchBatchResultDTO(19, 1, 38, []);
        });
        $dispatch->shouldReceive('execute')->once()->andReturnUsing(function () use ($orders) {
            $this->recordShipments($orders->last());

            return new DispatchBatchResultDTO(1, 1, 2, []);
        });
        $optimize->shouldReceive('execute')->once()
            ->andReturnUsing(function () {
                $this->assertDatabaseCount('golog_shipments', 40);

                return new OptimizeRoutesResultDTO(40, 1, 1, [], [], [], []);
            });

        $job = new DispatchPurchasedDeliveryOrdersJob;
        $job->handle($dispatch, $optimize);
        $job->handle($dispatch, $optimize);
    }

    private function recordShipments(DeliveryOrder $order): void
    {
        foreach (GoLogOperationType::cases() as $operation) {
            $order->gologShipments()->create([
                'golog_shipment_id' => "{$order->id}-{$operation->value}",
                'type' => $operation,
                'status' => GoLogShipmentStatus::PENDENTE,
            ]);
        }
    }
}
