<?php

namespace Tests\Unit\domain\Integrations\Geo\Provider\BrasilApi;

use Cultiva\Base\ValueObjects\Cep;
use Cultiva\Integrations\Geo\DTO\GeoAddressDTO;
use Cultiva\Integrations\Geo\Exceptions\GeoException;
use Cultiva\Integrations\Geo\Provider\BrasilApi\BrasilApiGeoProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BrasilApiGeoProviderTest extends TestCase
{

    public function test_should_return_geo_address_dto_when_brasil_api_returns_successful_response(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($fixture, 200),
        ]);

        $cep = new Cep('89010-025');
        $sut = $this->app->make(BrasilApiGeoProvider::class);

        // Action
        $result = $sut->searchByCep($cep);

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

    public function test_should_throw_geo_exception_when_brasil_api_returns_error_response(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_error.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($fixture, 404),
        ]);

        $cep = new Cep('89010025');
        $sut = $this->app->make(BrasilApiGeoProvider::class);

        // Action & Assert
        $this->expectException(GeoException::class);
        $this->expectExceptionCode(500);
        $sut->searchByCep($cep);
    }

    public function test_should_throw_geo_exception_when_brasil_api_connection_fails(): void
    {
        // Arrange
        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => fn() => throw new ConnectionException('Connection timed out'),
        ]);

        $cep = new Cep('89010025');
        $sut = $this->app->make(BrasilApiGeoProvider::class);

        // Action & Assert
        $this->expectException(GeoException::class);
        $this->expectExceptionCode(500);
        $sut->searchByCep($cep);
    }
}
