<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\ConfigureGoLogWebhookAction;
use Cultiva\Integrations\GoLog\DTO\GoLogWebhookTestResponseDTO;
use Mockery;
use Tests\TestCase;

final class ConfigureGoLogWebhookCommandTest extends TestCase
{
    public function test_should_configure_webhook_and_print_test_result(): void
    {
        // Arrange
        $action = Mockery::mock(ConfigureGoLogWebhookAction::class);
        $action->expects('execute')
            ->once()
            ->with('https://cultiva.ngrok.app/v1/golog/webhook', 'whsec_test', true, true)
            ->andReturn(new GoLogWebhookTestResponseDTO(true, 200, '{"status":"accepted"}', null));

        $this->app->instance(ConfigureGoLogWebhookAction::class, $action);

        // Action & Assert
        $this->artisan('golog:configure-webhook', [
            'url' => 'https://cultiva.ngrok.app/v1/golog/webhook',
            '--secret' => 'whsec_test',
            '--test' => true,
        ])
            ->expectsOutputToContain('Webhook configured successfully')
            ->expectsOutputToContain('Webhook test ping succeeded (HTTP 200)')
            ->assertExitCode(0);
    }
}
