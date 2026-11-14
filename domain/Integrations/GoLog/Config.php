<?php

declare(strict_types=1);

namespace Cultiva\Integrations\GoLog;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class Config
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function getBaseUrl(): string
    {
        return rtrim((string) $this->config->get('services.golog.base_url'), '/');
    }

    public function getEmail(): string
    {
        return (string) $this->config->get('services.golog.email');
    }

    public function getPassword(): string
    {
        return (string) $this->config->get('services.golog.password');
    }

    public function getTimeout(): int
    {
        return (int) $this->config->get('services.golog.timeout');
    }

    public function getTenantId(): ?string
    {
        $tenantId = $this->config->get('services.golog.tenant_id');

        return is_string($tenantId) && $tenantId !== '' ? $tenantId : null;
    }

    public function getWebhookSecret(): ?string
    {
        $secret = $this->config->get('services.golog.webhook_secret');

        return is_string($secret) && $secret !== '' ? $secret : null;
    }
}
