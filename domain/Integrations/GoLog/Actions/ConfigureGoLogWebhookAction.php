<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\GoLog\Clients\GoLogClient;
use Cultiva\Integrations\GoLog\Config;
use Cultiva\Integrations\GoLog\DTO\GoLogWebhookTestResponseDTO;

class ConfigureGoLogWebhookAction
{
    public function __construct(
        private readonly GoLogClient $client,
        private readonly Config $config,
    ) {}

    public function execute(
        string $url,
        ?string $secret = null,
        bool $active = true,
        bool $sendTest = false,
    ): ?GoLogWebhookTestResponseDTO {
        $resolvedSecret = $secret ?? $this->config->getWebhookSecret();

        if ($resolvedSecret === null || $resolvedSecret === '') {
            throw new CultivaException(422, 'Webhook secret is required. Pass --secret or set GOLOG_WEBHOOK_SECRET.');
        }

        $this->client->configureWebhook($url, $resolvedSecret, $active);

        return $sendTest ? $this->client->testWebhook() : null;
    }
}
