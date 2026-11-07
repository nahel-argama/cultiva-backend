<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\DispatchDeliveryOrdersBatchAction;
use Cultiva\Integrations\GoLog\DTO\DispatchBatchResultDTO;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DispatchDeliveryOrdersBatchCommandTest extends TestCase
{
    public function test_should_output_message_when_no_orders_found(): void
    {
        // Expects
        $this->instance(
            DispatchDeliveryOrdersBatchAction::class,
            Mockery::mock(DispatchDeliveryOrdersBatchAction::class, function (MockInterface $mock): void {
                $mock->shouldReceive('execute')
                    ->once()
                    ->andReturn(new DispatchBatchResultDTO(
                        totalOrders: 0,
                        batchesSent: 0,
                        shipmentsCreated: 0,
                        byCategory: [],
                    ));
            })
        );

        // Action & Assert
        $this->artisan('delivery:dispatch-orders-batch')
            ->expectsOutput('Scanning open delivery orders to aggregate and dispatch in batch...')
            ->expectsOutput('No open delivery orders found to dispatch.')
            ->assertSuccessful();
    }

    public function test_should_output_summary_when_orders_dispatched_successfully(): void
    {
        // Expects
        $this->instance(
            DispatchDeliveryOrdersBatchAction::class,
            Mockery::mock(DispatchDeliveryOrdersBatchAction::class, function (MockInterface $mock): void {
                $mock->shouldReceive('execute')
                    ->once()
                    ->andReturn(new DispatchBatchResultDTO(
                        totalOrders: 3,
                        batchesSent: 2,
                        shipmentsCreated: 3,
                        byCategory: [
                            'Frutas' => 2,
                            'Verduras' => 1,
                        ],
                    ));
            })
        );

        // Action & Assert
        $this->artisan('delivery:dispatch-orders-batch')
            ->expectsOutput('Scanning open delivery orders to aggregate and dispatch in batch...')
            ->expectsOutput('Found 3 open delivery order(s).')
            ->expectsOutput('Breakdown by Category:')
            ->expectsOutput('  - [Frutas]: 2 order(s)')
            ->expectsOutput('  - [Verduras]: 1 order(s)')
            ->expectsOutput('Batches sent: 2')
            ->expectsOutput('Shipments created successfully: 3')
            ->expectsOutput('Batch delivery orders successfully dispatched to GoLog TMS!')
            ->assertSuccessful();
    }
}
