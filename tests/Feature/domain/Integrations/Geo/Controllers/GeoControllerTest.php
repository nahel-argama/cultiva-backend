<?php

namespace Tests\Feature\domain\Integrations\Geo\Controllers;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GeoControllerTest extends TestCase
{

    #[DataProvider('invalidCepProvider')]
    public function test_should_return_404_when_cep_is_invalid(string $cep): void
    {
        // Arrange
        $url = "/v1/consult/cep/$cep";

        // Action & Assert
        $this->getJson($url)
            ->assertNotFound();
    }

    public function test_should_search_cep_with_success(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_success.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($fixture, 200),
        ]);

        $url = '/v1/consult/cep/89010025';

        // Action
        $response = $this->getJson($url);

        // Assert
        $response->assertOk()
            ->assertJson([
                'zip_code'     => '89010025',
                'state'        => 'SC',
                'city'         => 'Blumenau',
                'neighborhood' => 'Centro',
                'street'       => 'Rua Doutor Luiz de Freitas Melro',
                'latitude'     => -26.9244749,
                'longitude'    => -49.0629788,
            ]);
    }

    public function test_should_return_500_when_geo_provider_returns_error(): void
    {
        // Arrange
        $fixture = require base_path('tests/Fixtures/Integrations/Geo/BrasilApi/cep_v2_error.php');

        Http::fake([
            'https://brasilapi.com.br/api/cep/v2/89010025' => Http::response($fixture, 404),
        ]);

        $url = '/v1/consult/cep/89010025';

        // Action & Assert
        $this->getJson($url)
            ->assertStatus(500);
    }

    public static function invalidCepProvider(): array
    {
        return [
            ['1234567'],
            ['123456789'],
            ['abcdefgh'],
            ['!@#$%^&*'],
            ['1234abcd'],
        ];
    }
}
