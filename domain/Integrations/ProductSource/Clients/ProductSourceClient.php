<?php

namespace Cultiva\Integrations\ProductSource\Clients;

use Cultiva\Integrations\ProductSource\Config;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;

class ProductSourceClient
{
    public function __construct(
        private readonly Config $config,
        private readonly HttpFactory $http,
    ) {}

    public function getProduct(string $sourceProductId): Response
    {
        $baseUrl = $this->config->getBaseUrl();
        $timeout = $this->config->getTimeout();

        return $this->http->baseUrl($baseUrl)
            ->timeout($timeout)
            ->get("products/{$sourceProductId}");
    }
}
