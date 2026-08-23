<?php

namespace Cultiva\Integrations\Geo\DTO;

/**
 * @nicolas
 *
 * Um examplo legal de DTO:
 *
 * Aqui eu preciso criar um bloco de dados comum para qualquer integração de geolocalização,
 * de uma maneira que minha aplicação sempre lide com o mesmo bloco e a implementação (que não importa pra quem consome),
 * devolva esse dado.
 *
 * Tu falou que acha que DTO é últil em toda parte, se tu for extremamente restrito, daria pra dizer que sim. Mas tu vai
 * gerar mais boilerplate pro teu código se tu só sair usando. O DTO tem que ser usado como um contrato entre duas pontas,
 * e tu precisa de um contrato quando as coisas são instáveis ou precisam de extrema responsabilidade. Em uma rota de API,
 * tu não precisa converter pra um DTO pra passar pra uma action todas as vezes. Se a action foi desenhada pra rota, tu não precisa.
 * O DTO só vai adicionar complexidade.
 *
 * Mas agora se tu tem um action que tu usa em 30 partes diferentes do projeto, com uma carga de parâmetros chata e instável. Ai
 * faz sentido, porque você vai proteger as bordas.
 */
final class GeoAddressDTO
{

    public function __construct(
        public readonly string $zipCode,
        public readonly string $state,
        public readonly string $city,
        public readonly string $neighborhood,
        public readonly string $street,
        public readonly float  $latitude,
        public readonly float  $longitude,
    ) {}

    public function toArray(): array
    {
        return [
            'zip_code'     => $this->zipCode,
            'state'        => $this->state,
            'city'         => $this->city,
            'neighborhood' => $this->neighborhood,
            'street'       => $this->street,
            'latitude'     => $this->latitude,
            'longitude'    => $this->longitude,
        ];
    }
}
