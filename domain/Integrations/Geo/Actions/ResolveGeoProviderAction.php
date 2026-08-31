<?php

namespace Cultiva\Integrations\Geo\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\Geo\Contracts\GeoProviderContract;
use Cultiva\Integrations\Geo\GeoConfig;
use Cultiva\Integrations\Geo\Provider\BrasilApi\BrasilApiGeoProvider;
use Illuminate\Contracts\Container\Container;

class ResolveGeoProviderAction
{
    public function __construct(
        private readonly GeoConfig $config,
        private readonly Container $container,
    ) {}

    /**
     * @throws CultivaException
     */
    public function execute(): GeoProviderContract
    {
        $providerName = $this->config->getProvider();

        $providerClass = match ($providerName) {
            'brasilapi' => BrasilApiGeoProvider::class,
            default => throw new CultivaException(422, "Unsupported geo provider: {$providerName}"),
        };

        return $this->container->make($providerClass);
    }
}
