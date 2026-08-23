<?php

namespace Cultiva\Integrations\Geo\Provider\BrasilApi;

use Illuminate\Contracts\Config\Repository as ConfigRepository;

final class Config
{

    public function __construct(
        private readonly ConfigRepository $config
    ) {}

    public function getBaseUrl(): string
    {
        return $this->config->get('geo.providers.brasilapi.base_url');
    }

    public function getTimeout(): int
    {
        return (int) $this->config->get('geo.providers.brasilapi.timeout');
    }
}
