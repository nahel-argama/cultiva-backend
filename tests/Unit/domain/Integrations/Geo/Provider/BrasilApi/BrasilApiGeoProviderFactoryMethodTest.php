<?php

namespace Tests\Unit\domain\Integrations\Geo\Provider\BrasilApi;

use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Integrations\Geo\Provider\BrasilApi\BrasilApiGeoProviderFactoryMethod;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrasilApiGeoProviderFactoryMethodTest extends TestCase
{
    public function test_should_search_by_cep_through_the_geo_provider_product(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');
        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($fixture, 200),
        ]);

        $cep = new Cep('89010-025');
        $sut = $this->app->make(BrasilApiGeoProviderFactoryMethod::class);

        // Action
        $result = $sut->searchByCep($cep);

        // Assert
        $this->assertInstanceOf(GeoAddressDTO::class, $result);
        $this->assertSame('89010025', $result->zipCode);
    }
}
