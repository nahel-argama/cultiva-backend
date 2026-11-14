<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\ReconcileGoLogShipmentsAction;
use Mockery;
use Tests\TestCase;

final class ReconcileGoLogShipmentsCommandTest extends TestCase
{
    public function test_should_execute_reconciliation_and_report_updated_count(): void
    {
        // Arrange
        $action = Mockery::mock(ReconcileGoLogShipmentsAction::class);
        $action->expects('execute')
            ->once()
            ->andReturn(3);

        $this->app->instance(ReconcileGoLogShipmentsAction::class, $action);

        // Action & Assert
        $this->artisan('golog:reconcile-shipments')
            ->expectsOutputToContain('Reconciled 3 GoLog shipment(s).')
            ->assertExitCode(0);
    }
}
