<?php

namespace Cultiva\Integrations\Geo\Provider\BrasilApi;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\Contracts\GeoProviderContract;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Integrations\Geo\Provider\BrasilApi\Clients\Client;
use Cultiva\Integrations\Geo\Provider\BrasilApi\Transformer\CepAddressTransformer;
use Illuminate\Support\Facades\Lang;
use Override;
use Throwable;

/**
 * @nicolas
 *
 *  Aqui só faço uma implementação simples do serviço, já que ele tem só um método abstrato na interface
 */
final class BrasilApiGeoProvider implements GeoProviderContract
{
    public function __construct(
        private readonly Client $client,
        private readonly CepAddressTransformer $transformer
    ) {}

    #[Override]
    public function searchByCep(Cep $cep): GeoAddressDTO
    {
        try {
            $response = $this->client->make()->get($cep->value());

            if ($response->failed()) {
                throw new CultivaException(500, Lang::get('integrations.geo.failed_to_search'));
            }

            return $this->transformer->transform($response->json());
        } catch (CultivaException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new CultivaException(500, Lang::get('integrations.geo.failed_to_search'), $e);
        }
    }
}
