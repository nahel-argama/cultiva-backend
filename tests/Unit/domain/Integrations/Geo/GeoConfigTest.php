<?php

namespace Tests\Unit\domain\Integrations\Geo;

use Cultiva\Integrations\Geo\GeoConfig;
use Illuminate\Contracts\Config\Repository as Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GeoConfigTest extends TestCase
{

    public function test_should_return_default_provider_from_config(): void
    {
        // Arrange & Expects
        $configMock = Mockery::mock(Config::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('geo.default')
                ->andReturn('brasilapi');
        });

        $sut = new GeoConfig($configMock);

        // Action
        $result = $sut->getProvider();

        // Assert
        $this->assertSame('brasilapi', $result);
    }

    public function test_should_return_provider_config(): void
    {
        // Arrange
        $expectedConfig = [
            'base_url' => 'https://brasilapi.com.br/api/cep/v2',
            'timeout' => 10,
        ];

        // Expects
        $configMock = Mockery::mock(Config::class, function (MockInterface $mock) use ($expectedConfig) {
            $mock->shouldReceive('get')
                ->once()
                ->with('geo.providers.brasilapi')
                ->andReturn($expectedConfig);
        });

        $sut = new GeoConfig($configMock);

        // Action
        $result = $sut->getProviderConfig('brasilapi');

        // Assert
        $this->assertSame($expectedConfig, $result);
    }
}
