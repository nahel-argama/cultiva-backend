<?php

namespace Cultiva\Integrations\Geo\Provider\BrasilApi;

use Cultiva\Integrations\Geo\Contracts\GeoProviderContract;
use Cultiva\Integrations\Geo\GeoProviderFactoryMethod;
use Cultiva\Integrations\Geo\Provider\BrasilApi\Clients\Client;
use Cultiva\Integrations\Geo\Provider\BrasilApi\Transformer\CepAddressTransformer;

final class BrasilApiGeoProviderFactoryMethod extends GeoProviderFactoryMethod
{
    public function __construct(
        private readonly Client $client,
        private readonly CepAddressTransformer $transformer,
    ) {}

    protected function createProvider(): GeoProviderContract
    {
        return new BrasilApiGeoProvider($this->client, $this->transformer);
    }
}
