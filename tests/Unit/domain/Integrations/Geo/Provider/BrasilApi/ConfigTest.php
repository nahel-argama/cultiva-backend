<?php

namespace Tests\Unit\domain\Integrations\Geo\Provider\BrasilApi;

use Cultiva\Integrations\Geo\Provider\BrasilApi\Config as BrasilApiConfig;
use Illuminate\Contracts\Config\Repository as Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ConfigTest extends TestCase
{

    public function test_should_return_base_url_for_brasil_api(): void
    {
        // Arrange & Expects
        $configMock = Mockery::mock(Config::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('geo.providers.brasilapi.base_url')
                ->andReturn('https://brasilapi.com.br/api/cep/v2');
        });

        $sut = new BrasilApiConfig($configMock);

        // Action
        $result = $sut->getBaseUrl();

        // Assert
        $this->assertSame('https://brasilapi.com.br/api/cep/v2', $result);
    }

    public function test_should_return_timeout_for_brasil_api(): void
    {
        // Arrange & Expects
        $configMock = Mockery::mock(Config::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('geo.providers.brasilapi.timeout')
                ->andReturn(10);
        });

        $sut = new BrasilApiConfig($configMock);

        // Action
        $result = $sut->getTimeout();

        // Assert
        $this->assertSame(10, $result);
    }
}
