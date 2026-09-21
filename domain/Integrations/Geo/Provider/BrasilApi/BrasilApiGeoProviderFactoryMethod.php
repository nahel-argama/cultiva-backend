<?php

namespace Cultiva\Integrations\Geo\Provider\BrasilApi;

use Cultiva\Integrations\Geo\Contracts\GeoProviderContract;
use Cultiva\Integrations\Geo\Decorators\CachedGeoProviderDecorator;
use Cultiva\Integrations\Geo\GeoProviderFactoryMethod;
use Cultiva\Integrations\Geo\Adapters\BrasilApiGeoAdapter;
use Cultiva\Integrations\Geo\Provider\BrasilApi\Clients\Client;
use Cultiva\Integrations\Geo\Provider\BrasilApi\Transformer\CepAddressTransformer;
use Illuminate\Contracts\Cache\Repository;

final class BrasilApiGeoProviderFactoryMethod extends GeoProviderFactoryMethod
{
    public function __construct(
        private readonly Client $client,
        private readonly CepAddressTransformer $transformer,
        private readonly Repository $cache,
    ) {}

    protected function createProvider(): GeoProviderContract
    {
        $adapter = new BrasilApiGeoAdapter($this->client, $this->transformer);

        return new CachedGeoProviderDecorator($adapter, $this->cache);
    }
}
