<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\OptimizeDeliveryRoutesAction;
use Cultiva\Integrations\GoLog\DTO\OptimizeRoutesResultDTO;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class OptimizeDeliveryRoutesCommandTest extends TestCase
{
    public function test_should_output_message_when_no_pending_shipments_found(): void
    {
        // Expects
        $this->instance(
            OptimizeDeliveryRoutesAction::class,
            Mockery::mock(OptimizeDeliveryRoutesAction::class, function (MockInterface $mock): void {
                $mock->shouldReceive('execute')
                    ->once()
                    ->with(null)
                    ->andReturn(new OptimizeRoutesResultDTO(
                        shipmentsOptimized: 0,
                        routesCount: 0,
                        workSchedulesUsed: 0,
                        plannedShipmentIds: [],
                        skippedShipmentIds: [],
                        transportIds: [],
                        workScheduleIds: [],
                    ));
            })
        );

        // Action & Assert
        $this->artisan('delivery:optimize-routes')
            ->expectsOutput('Scanning pending GoLog shipments for route optimization...')
            ->expectsOutput('No pending GoLog shipments found to optimize.')
            ->assertSuccessful();
    }

    public function test_should_output_summary_when_routes_optimized_successfully(): void
    {
        // Expects
        $this->instance(
            OptimizeDeliveryRoutesAction::class,
            Mockery::mock(OptimizeDeliveryRoutesAction::class, function (MockInterface $mock): void {
                $mock->shouldReceive('execute')
                    ->once()
                    ->with('2026-11-16')
                    ->andReturn(new OptimizeRoutesResultDTO(
                        shipmentsOptimized: 4,
                        routesCount: 2,
                        workSchedulesUsed: 3,
                        plannedShipmentIds: [
                            '11111111-1111-1111-1111-111111111111',
                            '22222222-2222-2222-2222-222222222222',
                        ],
                        skippedShipmentIds: [
                            '33333333-3333-3333-3333-333333333333',
                        ],
                        transportIds: [
                            't1111111-1111-1111-1111-111111111111',
                        ],
                        workScheduleIds: [
                            'c1111111-1111-1111-1111-111111111111',
                        ],
                    ));
            })
        );

        // Action & Assert
        $this->artisan('delivery:optimize-routes', ['--date' => '2026-11-16'])
            ->expectsOutput('Scanning pending GoLog shipments for route optimization...')
            ->expectsOutput('Routes created: 2')
            ->expectsOutput('Shipments optimized: 4')
            ->expectsOutput('Shipments skipped (remained PENDENTE): 1')
            ->expectsOutput('Driver work schedules used: 3')
            ->expectsOutput('Routes optimized successfully in GoLog TMS!')
            ->assertSuccessful();
    }
}
