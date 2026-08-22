<?php

namespace Tests\Unit\domain\Integrations\Geo\Provider\BrasilApi\Transformer;

use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Integrations\Geo\Provider\BrasilApi\Transformer\CepAddressTransformer;
use Tests\TestCase;

class CepAddressTransformerTest extends TestCase
{

    public function test_should_transform_brasil_api_payload_into_geo_address_dto(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');
        $payload = json_decode($fixture, true);

        $sut = new CepAddressTransformer();

        // Action
        $result = $sut->transform($payload);

        // Assert
        $this->assertInstanceOf(GeoAddressDTO::class, $result);
        $this->assertSame('89010025', $result->zipCode);
        $this->assertSame('SC', $result->state);
        $this->assertSame('Blumenau', $result->city);
        $this->assertSame('Centro', $result->neighborhood);
        $this->assertSame('Rua Doutor Luiz de Freitas Melro', $result->street);
        $this->assertSame(-26.9244749, $result->latitude);
        $this->assertSame(-49.0629788, $result->longitude);
    }
}
