<?php

namespace Cultiva\Integrations\Geo\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\Geo\GeoConfig;
use Cultiva\Integrations\Geo\GeoProviderFactoryMethod;
use Cultiva\Integrations\Geo\Provider\BrasilApi\BrasilApiGeoProviderFactoryMethod;
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
    public function execute(): GeoProviderFactoryMethod
    {
        $providerName = $this->config->getProvider();

        $providerClass = match ($providerName) {
            'brasilapi' => BrasilApiGeoProviderFactoryMethod::class,
            default => throw new CultivaException(422, "Unsupported geo provider: {$providerName}"),
        };

        return $this->container->make($providerClass);
    }
}
