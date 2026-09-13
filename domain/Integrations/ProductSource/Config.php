<?php

namespace Cultiva\Integrations\ProductSource;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class Config
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function getBaseUrl(): string
    {
        return rtrim((string) $this->config->get('services.product_source.base_url'), '/');
    }

    public function getTimeout(): int
    {
        return (int) $this->config->get('services.product_source.timeout', 5);
    }
}
