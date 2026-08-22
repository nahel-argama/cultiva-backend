<?php

namespace Cultiva\Integrations\Geo\Provider\BrasilApi;

use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Contracts\GeoProviderContract;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Integrations\Geo\Provider\BrasilApi\Actions\SearchByCepAction;
use Illuminate\Contracts\Container\Container;
use Override;

/**
 * @nicolas
 *
 * Na implementação do serviço eu quis experimentar algo diferente. Aqui to tentando imitar como uma controller do laravel
 * chama os métodos dela a partir do arquivo de rotas. Ou seja, quero deixar cada método da implementação isolado em seu
 * contexto. Porque no meu trabalho lidei com uma situação em que não consegui fazer assim e a classe ficou pesada. Só vendo
 * pra entender. Desse jeito achei legal, pode opinar se quiser.
 *
 * Outra técnica que a gente poderia ter utilizado é a pattern saloon, não fui muito a fundo mas fiz um overview dos conceitos.
 * Ela é legal, mas não queria outra layer de arquitetura pro projeto, por isso mantive o fluxo de action mesmo aqui.
 */
final class BrasilApiGeoProvider implements GeoProviderContract
{

    public function __construct(
        private readonly Container $container,
    ) {}

    #[Override]
    public function searchByCep(Cep $cep): GeoAddressDTO
    {
        return $this->container->make(SearchByCepAction::class)->execute($cep);
    }
}
