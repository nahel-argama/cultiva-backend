<?php

namespace Cultiva\Integrations\Geo\Provider\BrasilApi\Transformer;

use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;

/**
 * @nicolas
 *
 * Um tranformer vai fazer exatamente o que o nome dele diz: pegar um input e transformar em um output, nada a mais.
 * A princípio parece uma camada extra de complexidade, mas pense em casos em que você precisa fazer o parse especial
 * de algum campo no input, ou qualquer lógica do tipo.
 *
 * Essa classe alivia a responsabilidade da Action de ter que lidar com regras de parsing e facilita a vida de quem
 * for testar, já que você pode garantir que o parse feito no transformer funciona de maneira isolada.
 *
 * Você vai ver transformer sendo usados de outras formas, tipo em um controller pra tranformar o resultado de um action
 * na resposta da API. Mas o conceito é sempre o mesmo: pegar um input e transformar em um output, nada a mais.
 *
 * Quem chama o transformer não precisa saber lógica de parse, só entregar o que ele precisa e receber o resultado.
 *
 * Obs: esse exemplo não é muito poggers, já que to só acessando um array. Mas vi a chance de te mostrar como funfa
 */
final class CepAddressTransformer
{

    public function __construct() {}

    public function transform(array $data): GeoAddressDTO
    {
        $coordinates = $data['location']['coordinates'] ?? [];

        return new GeoAddressDTO(
            zipCode: $data['cep'],
            state: $data['state'],
            city: $data['city'],
            neighborhood: $data['neighborhood'],
            street: $data['street'],
            latitude: (float) ($coordinates['latitude'] ?? 0.0),
            longitude: (float) ($coordinates['longitude'] ?? 0.0),
        );
    }
}
