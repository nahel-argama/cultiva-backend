<?php

namespace Cultiva\Integrations\Geo\Actions;

use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;

class SearchCepAction
{

    public function __construct(
        private readonly ResolveGeoProviderAction $resolveGeoProvider,
    ) {}

    public function execute(Cep $cep): GeoAddressDTO
    {
        $factoryMethod = $this->resolveGeoProvider->execute();

        return $factoryMethod->searchByCep($cep);
    }
}
