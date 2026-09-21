<?php

namespace Cultiva\Integrations\Geo;

use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Contracts\GeoProviderContract;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;

abstract class GeoProviderFactoryMethod
{
    public function searchByCep(Cep $cep): GeoAddressDTO
    {
        return $this->createProvider()->searchByCep($cep);
    }

    abstract protected function createProvider(): GeoProviderContract;
}
