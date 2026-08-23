<?php

namespace Cultiva\Integrations\Geo;

use Illuminate\Contracts\Config\Repository as Config;

/**
 * @nicolas
 *
 * Aqui tem algo que não deu tempo de explicar, mas o Laravel tem um recurso de configs. Se tu reparar na pasta raiz do projeto
 * tem o diretório config, e dentro dele um monte de arquivo PHP que retornam arrays.
 *
 * Esses arquivos vão fazer um wrap do valores da nossa .env, normalmente, mas podem ter outros valores fixo também.
 *
 * A gente consegue acessar esses valores de qualquer canto do projeto, com a função global config(). Aqui nesse exemplo eu
 * injeto a classe de config no contructor por convenção, mas nesse casoa função global não teria problema. Sempre depende se
 * você quer ou não testar o comportamento.
 *
 * Pra acessar os valores da config tu sempre vai usar o caminho na da chave no array contando com o nome do arquivo de config, como
 * se fosse um objeto: config('geo.providers.brasilapi.base_url') vai te dar o valor da chave base_url do array dentro do arquivo geo.php.
 */
final class GeoConfig
{

    public function __construct(
        private readonly Config $config
    ) {}

    public function getProvider(): string
    {
        return $this->config->get('geo.default');
    }

    public function getProviderConfig(string $provider): array
    {
        return $this->config->get("geo.providers.{$provider}");
    }
}
