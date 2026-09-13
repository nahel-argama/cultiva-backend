<?php

namespace Tests\Unit\domain\Integrations\ProductSource;

use Cultiva\Integrations\ProductSource\Config as ProductSourceConfig;
use Illuminate\Contracts\Config\Repository as Config;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ConfigTest extends TestCase
{
    public function test_should_return_base_url_for_product_source(): void
    {
        // Arrange & Expects
        $configMock = Mockery::mock(Config::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('services.product_source.base_url')
                ->andReturn('http://product-source.test/api/');
        });

        $sut = new ProductSourceConfig($configMock);

        // Action
        $result = $sut->getBaseUrl();

        // Assert
        $this->assertSame('http://product-source.test/api', $result);
    }

    public function test_should_return_timeout_for_product_source(): void
    {
        // Arrange & Expects
        $configMock = Mockery::mock(Config::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->once()
                ->with('services.product_source.timeout', 5)
                ->andReturn(10);
        });

        $sut = new ProductSourceConfig($configMock);

        // Action
        $result = $sut->getTimeout();

        // Assert
        $this->assertSame(10, $result);
    }
}
