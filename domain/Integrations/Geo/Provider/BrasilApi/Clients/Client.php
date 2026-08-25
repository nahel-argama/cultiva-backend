<?php

namespace Cultiva\Integrations\Geo\Provider\BrasilApi\Clients;

use Cultiva\Integrations\Geo\Provider\BrasilApi\Config;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;

class Client
{

    public function __construct(
        private readonly Config $config,
        private readonly HttpFactory $http
    ) {}

    public function make(): PendingRequest
    {
        $timeout = $this->config->getTimeout();
        $baseUrl = $this->config->getBaseUrl();

        return $this->http->baseUrl($baseUrl)->timeout($timeout);
    }
}
