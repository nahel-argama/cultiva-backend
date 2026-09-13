<?php

namespace Tests\Unit\domain\Integrations\ProductSource\Clients;

use Cultiva\Integrations\ProductSource\Clients\ProductSourceClient;
use Cultiva\Integrations\ProductSource\Config;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class ProductSourceClientTest extends TestCase
{
    public function test_should_send_get_request_to_product_source_and_return_response(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        config(['services.product_source.timeout' => 5]);

        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_success.php');

        Http::fake([
            'http://product-source.test/api/products/0002' => Http::response($fixture, 200),
        ]);

        $sut = $this->app->make(ProductSourceClient::class);

        // Action
        $response = $sut->getProduct('0002');

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->successful());
        $this->assertSame('0002', $response->json('id'));
        $this->assertSame('Tomate Italiano', $response->json('name'));
        Http::assertSent(function ($request) {
            return $request->url() === 'http://product-source.test/api/products/0002'
                && $request->method() === 'GET';
        });
    }

    public function test_should_use_config_to_retrieve_base_url_and_timeout(): void
    {
        // Arrange & Expects
        $configRepo = Mockery::mock(ConfigRepository::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')
                ->with('services.product_source.base_url')
                ->andReturn('http://custom-host.test/api');
            $mock->shouldReceive('get')
                ->with('services.product_source.timeout', 5)
                ->andReturn(8);
        });

        $config = new Config($configRepo);

        Http::fake([
            'http://custom-host.test/api/products/0002' => Http::response(['id' => '0002', 'name' => 'Tomate'], 200),
        ]);

        $sut = new ProductSourceClient($config, $this->app->make(HttpFactory::class));

        // Action
        $response = $sut->getProduct('0002');

        // Assert
        $this->assertTrue($response->successful());
        Http::assertSent(function ($request) {
            return $request->url() === 'http://custom-host.test/api/products/0002';
        });
    }
}
