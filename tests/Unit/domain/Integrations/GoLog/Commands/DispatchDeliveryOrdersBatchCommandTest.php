<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\DispatchDeliveryOrdersBatchAction;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class DispatchDeliveryOrdersBatchCommandTest extends TestCase
{
    public function test_should_output_message_when_no_orders_found(): void
    {
        $this->instance(
            DispatchDeliveryOrdersBatchAction::class,
            Mockery::mock(DispatchDeliveryOrdersBatchAction::class, function (MockInterface $mock) {
                $mock->shouldReceive('execute')
                    ->once()
                    ->andReturn([
                        'total_orders' => 0,
                        'batches_sent' => 0,
                        'success_count' => 0,
                        'error_count' => 0,
                        'by_category' => [],
                        'errors' => [],
                    ]);
            })
        );

        $this->artisan('delivery:dispatch-orders-batch')
            ->expectsOutput('Scanning open delivery orders to aggregate and dispatch in batch...')
            ->expectsOutput('No open delivery orders found to dispatch.')
            ->assertSuccessful();
    }

    public function test_should_output_summary_when_orders_dispatched_successfully(): void
    {
        $this->instance(
            DispatchDeliveryOrdersBatchAction::class,
            Mockery::mock(DispatchDeliveryOrdersBatchAction::class, function (MockInterface $mock) {
                $mock->shouldReceive('execute')
                    ->once()
                    ->andReturn([
                        'total_orders' => 3,
                        'batches_sent' => 2,
                        'success_count' => 3,
                        'error_count' => 0,
                        'by_category' => [
                            'Frutas' => 2,
                            'Verduras' => 1,
                        ],
                        'errors' => [],
                    ]);
            })
        );

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
