<?php

namespace Cultiva\Integrations\Geo\Decorators;

use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Contracts\GeoProviderContract;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;

abstract class GeoProviderDecorator implements GeoProviderContract
{
    public function __construct(
        protected readonly GeoProviderContract $provider,
    ) {}

    public function searchByCep(Cep $cep): GeoAddressDTO
    {
        return $this->provider->searchByCep($cep);
    }
}
