<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Commands;

use Cultiva\Integrations\GoLog\Actions\ConfigureGoLogWebhookAction;
use Illuminate\Console\Command;

class ConfigureGoLogWebhookCommand extends Command
{
    protected $signature = 'golog:configure-webhook
        {url : Public webhook endpoint URL}
        {--secret= : Shared HMAC secret (defaults to GOLOG_WEBHOOK_SECRET)}
        {--inactive : Register the webhook as inactive}
        {--test : Trigger a TEST_PING delivery after configuring}';

    protected $description = 'Configure and optionally test the GoLog TMS webhook endpoint';

    public function handle(ConfigureGoLogWebhookAction $action): int
    {
        /** @var string $url */
        $url = $this->argument('url');
        /** @var ?string $secret */
        $secret = $this->option('secret');
        $active = ! (bool) $this->option('inactive');
        $sendTest = (bool) $this->option('test');

        $testResponse = $action->execute($url, $secret, $active, $sendTest);

        $this->info('Webhook configured successfully.');

        if ($testResponse === null) {
            return self::SUCCESS;
        }

        if ($testResponse->success) {
            $this->info("Webhook test ping succeeded (HTTP {$testResponse->statusCode}).");

            return self::SUCCESS;
        }

        $this->error("Webhook test ping failed (HTTP {$testResponse->statusCode}): {$testResponse->error}");

        return self::FAILURE;
    }
}
