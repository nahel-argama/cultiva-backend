<?php

namespace Tests\Unit\domain\Integrations\ProductSource\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Integrations\ProductSource\Actions\GetProductAction;
use Cultiva\Integrations\ProductSource\DTO\ProductDTO;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GetProductActionTest extends TestCase
{
    public function test_should_return_product_dto_with_exact_textual_id_and_name(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_success.php');
        Http::fake([
            'http://product-source.test/api/products/0002' => Http::response($fixture, 200),
        ]);
        $sut = $this->app->make(GetProductAction::class);

        // Action
        $result = $sut->execute('0002');

        // Assert
        $this->assertInstanceOf(ProductDTO::class, $result);
        $this->assertSame('0002', $result->id);
        $this->assertSame('Tomate Italiano', $result->name);
        Http::assertSentCount(1);
    }

    public function test_should_fallback_to_normal_name_when_presentation_name_is_missing(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_without_presentation_name.php');
        Http::fake([
            'http://product-source.test/api/products/2' => Http::response($fixture, 200),
        ]);
        $sut = $this->app->make(GetProductAction::class);

        // Action
        $result = $sut->execute('2');

        // Assert
        $this->assertSame('Tomate', $result->name);
    }

    public function test_should_throw_422_when_product_is_not_found(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        Http::fake([
            'http://product-source.test/api/products/99' => Http::response(['detail' => 'Product not found'], 404),
        ]);
        $sut = $this->app->make(GetProductAction::class);

        // Action & Assert
        try {
            $sut->execute('99');
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_should_throw_503_when_connection_fails(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        Http::fake([
            'http://product-source.test/api/products/2' => fn () => throw new ConnectionException('Connection timed out'),
        ]);
        $sut = $this->app->make(GetProductAction::class);

        // Action & Assert
        try {
            $sut->execute('2');
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
        }
    }

    public function test_should_throw_503_without_retry_when_service_returns_server_error(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        Http::fake([
            'http://product-source.test/api/products/2' => Http::response([], 500),
        ]);
        $sut = $this->app->make(GetProductAction::class);

        // Action & Assert
        try {
            $sut->execute('2');
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $exception) {
            $this->assertSame(503, $exception->getStatusCode());
            Http::assertSentCount(1);
        }
    }

}
