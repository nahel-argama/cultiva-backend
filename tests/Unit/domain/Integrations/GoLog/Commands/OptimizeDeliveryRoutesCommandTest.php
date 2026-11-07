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
                    ->andReturn(new OptimizeRoutesResultDTO(
                        shipmentsOptimized: 0,
                        workSchedulesUsed: 0,
                        shipmentIds: [],
                        workScheduleIds: [],
                        response: '',
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
                    ->andReturn(new OptimizeRoutesResultDTO(
                        shipmentsOptimized: 6,
                        workSchedulesUsed: 3,
                        shipmentIds: [
                            '11111111-1111-1111-1111-111111111111',
                            '22222222-2222-2222-2222-222222222222',
                        ],
                        workScheduleIds: [
                            'c1111111-1111-1111-1111-111111111111',
                        ],
                        response: '"Rotas otimizadas com sucesso"',
                    ));
            })
        );

        // Action & Assert
        $this->artisan('delivery:optimize-routes')
            ->expectsOutput('Scanning pending GoLog shipments for route optimization...')
            ->expectsOutput('Shipments optimized: 6')
            ->expectsOutput('Driver work schedules used: 3')
            ->expectsOutput('Routes optimized successfully in GoLog TMS!')
            ->assertSuccessful();
    }
}
