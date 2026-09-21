<?php

namespace Cultiva\Integrations\Geo\Decorators;

use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Contracts\GeoProviderContract;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Illuminate\Contracts\Cache\Repository;

final class CachedGeoProviderDecorator extends GeoProviderDecorator
{
    public function __construct(
        GeoProviderContract $provider,
        private readonly Repository $cache,
    ) {
        parent::__construct($provider);
    }

    public function searchByCep(Cep $cep): GeoAddressDTO
    {
        return $this->cache->remember(
            "geo:cep:{$cep->value()}",
            3600,
            fn (): GeoAddressDTO => parent::searchByCep($cep),
        );
    }
}
