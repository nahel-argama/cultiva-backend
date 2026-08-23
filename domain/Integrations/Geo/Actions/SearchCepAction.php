<?php

namespace Cultiva\Integrations\Geo\Actions;

use Cultiva\Base\ValueObjects\Cep;

class SearchCepAction
{

    public function __construct(
        private readonly ResolveGeoProviderAction $resolveGeoProvider,
    ) {}

    public function execute(Cep $cep): array
    {
        $provider = $this->resolveGeoProvider->execute();

        $result = $provider->searchByCep($cep);

        return $result->toArray();
    }
}
