<?php

namespace Cultiva\Integrations\Geo\Contracts;

use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;

/**
 * @nicolas
 *
 * Por que um interface? Bom, pensa em quem vai consumir o serviço de geolocalização, seria uma verdadeira cada
 * se um dev precisasse mudar todo lugar em que ele consumiu o serviço só porque a gente mudou de provider.
 *
 * Quando a gente centraliza tudo em uma interface/contrato, o caminho pra consumir o serviço é sempre o mesmo, já
 * que a implementação fica pra quem implementa a interface. Escondendo detalhes da implementação e facilitando a vida
 * de quem consome o serviço.
 *
 * Claro que o preço disso é aumento de complexidade, certo custo de performance inegável, mas minímo.
 */
interface GeoProviderContract
{

    public function searchByCep(Cep $cep): GeoAddressDTO;
}
