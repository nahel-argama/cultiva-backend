<?php

namespace Cultiva\Integrations\Geo\Provider\BrasilApi\Actions;

use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Integrations\Geo\Exceptions\GeoException;
use Cultiva\Integrations\Geo\Provider\BrasilApi\Clients\Client;
use Cultiva\Integrations\Geo\Provider\BrasilApi\Transformer\CepAddressTransformer;
use Illuminate\Support\Facades\Lang;
use Throwable;

final class SearchByCepAction
{

    public function __construct(
        private readonly Client                $client,
        private readonly CepAddressTransformer $transformer
    ) {}

    public function execute(Cep $cep): GeoAddressDTO
    {
        try {
            $response = $this->client->make()->get($cep->value());

            if ($response->failed()) {
                throw new GeoException(Lang::get('integrations.geo.failed_to_search'), 500);
            }

            return $this->transformer->transform($response->json());
        } catch (GeoException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new GeoException(Lang::get('integrations.geo.failed_to_search'), 500, $e);
        }
    }
}
