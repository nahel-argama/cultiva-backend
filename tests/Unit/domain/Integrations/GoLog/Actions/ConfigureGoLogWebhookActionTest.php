<?php

declare(strict_types=1);

namespace Tests\Unit\domain\Integrations\GoLog\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Actions\ConfigureGoLogWebhookAction;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\Config;
use Cultiva\Integrations\GoLog\DTO\GoLogWebhookTestResponseDTO;
use Mockery;
use Tests\TestCase;

final class ConfigureGoLogWebhookActionTest extends TestCase
{
    public function test_should_configure_webhook_using_configured_secret_without_test_ping(): void
    {
        // Arrange
        config(['services.golog.webhook_secret' => 'whsec_env_secret']);

        $client = Mockery::mock(GoLogClient::class);
        $client->expects('configureWebhook')
            ->once()
            ->with('https://example.ngrok.app/v1/golog/webhook', 'whsec_env_secret', true);
        $client->shouldNotReceive('testWebhook');

        $sut = new ConfigureGoLogWebhookAction($client, $this->app->make(Config::class));

        // Action
        $result = $sut->execute('https://example.ngrok.app/v1/golog/webhook');

        // Assert
        $this->assertNull($result);
    }

    public function test_should_configure_and_trigger_test_webhook_with_explicit_secret(): void
    {
        // Arrange
        $client = Mockery::mock(GoLogClient::class);
        $client->expects('configureWebhook')
            ->once()
            ->with('https://example.ngrok.app/v1/golog/webhook', 'whsec_cli_secret', false);
        $client->expects('testWebhook')
            ->once()
            ->andReturn(new GoLogWebhookTestResponseDTO(true, 200, '{"status":"accepted"}', null));

        $sut = new ConfigureGoLogWebhookAction($client, $this->app->make(Config::class));

        // Action
        $result = $sut->execute(
            url: 'https://example.ngrok.app/v1/golog/webhook',
            secret: 'whsec_cli_secret',
            active: false,
            sendTest: true,
        );

        // Assert
        $this->assertInstanceOf(GoLogWebhookTestResponseDTO::class, $result);
        $this->assertTrue($result->success);
        $this->assertSame(200, $result->statusCode);
    }

    public function test_should_throw_when_no_secret_is_provided_or_configured(): void
    {
        // Arrange
        config(['services.golog.webhook_secret' => null]);
        $client = Mockery::mock(GoLogClient::class);
        $sut = new ConfigureGoLogWebhookAction($client, $this->app->make(Config::class));

        // Action & Assert
        $this->expectException(CultivaException::class);
        $sut->execute('https://example.ngrok.app/v1/golog/webhook');
    }
}
