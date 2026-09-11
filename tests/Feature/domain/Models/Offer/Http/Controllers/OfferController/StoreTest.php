<?php

namespace Tests\Feature\domain\Models\Offer\Http\Controllers\OfferController;

use Database\Factories\CategoryFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_offer_for_authenticated_producer(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_success.php');
        Http::fake([
            'http://product-source.test/api/products/0002' => Http::response($fixture, 200),
        ]);
        $producer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create(['name' => 'Categoria de teste']);
        Sanctum::actingAs($producer->company->user, ['access']);
        $payload = [
            'source_product_id' => '0002',
            'category_id' => $category->id,
            'unit_price' => '12.50',
            'total_quantity' => 20,
        ];

        // Action
        $response = $this->postJson('/v1/offers', $payload);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('data.source_product_id', '0002')
            ->assertJsonPath('data.product_name', 'Tomate Italiano')
            ->assertJsonPath('data.category.id', $category->id)
            ->assertJsonPath('data.unit_price', '12.50')
            ->assertJsonPath('data.total_quantity', 20)
            ->assertJsonPath('data.reserved_quantity', 0)
            ->assertJsonPath('data.available_quantity', 20)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.is_visible', true);
        $this->assertDatabaseHas('offers', [
            'producer_id' => $producer->id,
            'source_product_id' => '0002',
            'product_name' => 'Tomate Italiano',
            'category_id' => $category->id,
            'unit_price' => '12.50',
            'total_quantity' => 20,
            'reserved_quantity' => 0,
            'status' => 'active',
        ]);
    }

    public function test_should_ignore_client_status_and_snapshot_fallback_name(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_without_presentation_name.php');
        Http::fake([
            'http://product-source.test/api/products/2' => Http::response($fixture, 200),
        ]);
        $producer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);
        $payload = [
            'source_product_id' => 2,
            'category_id' => $category->id,
            'unit_price' => '9.90',
            'total_quantity' => 5,
            'status' => 'inactive',
            'producer_id' => 999,
            'reserved_quantity' => 99,
        ];

        // Action
        $response = $this->postJson('/v1/offers', $payload);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('data.source_product_id', '2')
            ->assertJsonPath('data.product_name', 'Tomate')
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.is_visible', true);
        $this->assertDatabaseHas('offers', [
            'producer_id' => $producer->id,
            'source_product_id' => '2',
            'product_name' => 'Tomate',
        ]);
    }

    #[DataProvider('invalidPayloadProvider')]
    public function test_should_return_422_for_invalid_offer_payload(array $changes): void
    {
        // Arrange
        Http::fake();
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);
        $payload = array_merge([
            'source_product_id' => '2',
            'category_id' => 1,
            'unit_price' => '12.50',
            'total_quantity' => 10,
        ], $changes);

        // Action
        $response = $this->postJson('/v1/offers', $payload);

        // Assert
        $response->assertUnprocessable();
        $this->assertDatabaseCount('offers', 0);
        Http::assertNothingSent();
    }

    public static function invalidPayloadProvider(): array
    {
        return [
            'empty source product id' => [['source_product_id' => '']],
            'invalid category id' => [['category_id' => 'one']],
            'zero unit price' => [['unit_price' => '0.00']],
            'more than two decimal places' => [['unit_price' => '1.999']],
            'negative total quantity' => [['total_quantity' => -1]],
            'invalid status' => [['status' => 'draft']],
        ];
    }

    public function test_should_return_422_when_category_does_not_exist(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_without_presentation_name.php');
        Http::fake([
            'http://product-source.test/api/products/2' => Http::response($fixture, 200),
        ]);
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);
        $payload = [
            'source_product_id' => '2',
            'category_id' => 999,
            'unit_price' => '12.50',
            'total_quantity' => 10,
        ];

        // Action
        $response = $this->postJson('/v1/offers', $payload);

        // Assert
        $response->assertUnprocessable();
        $this->assertDatabaseCount('offers', 0);
    }

    public function test_should_return_422_when_source_product_does_not_exist(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        Http::fake([
            'http://product-source.test/api/products/99' => Http::response(['detail' => 'Product not found'], 404),
        ]);
        $producer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);
        $payload = [
            'source_product_id' => '99',
            'category_id' => $category->id,
            'unit_price' => '12.50',
            'total_quantity' => 10,
        ];

        // Action
        $response = $this->postJson('/v1/offers', $payload);

        // Assert
        $response->assertUnprocessable();
        $this->assertDatabaseCount('offers', 0);
    }

    public function test_should_return_503_when_product_source_connection_fails(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        Http::fake([
            'http://product-source.test/api/products/2' => fn () => throw new ConnectionException('Connection timed out'),
        ]);
        $producer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);
        $payload = [
            'source_product_id' => '2',
            'category_id' => $category->id,
            'unit_price' => '12.50',
            'total_quantity' => 10,
        ];

        // Action
        $response = $this->postJson('/v1/offers', $payload);

        // Assert
        $response->assertServiceUnavailable();
        $this->assertDatabaseCount('offers', 0);
    }

    public function test_should_return_503_when_product_source_returns_server_error(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        Http::fake([
            'http://product-source.test/api/products/2' => Http::response([], 500),
        ]);
        $producer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);
        $payload = [
            'source_product_id' => '2',
            'category_id' => $category->id,
            'unit_price' => '12.50',
            'total_quantity' => 10,
        ];

        // Action
        $response = $this->postJson('/v1/offers', $payload);

        // Assert
        $response->assertServiceUnavailable();
        $this->assertDatabaseCount('offers', 0);
        Http::assertSentCount(1);
    }

    public function test_should_return_401_without_token(): void
    {
        // Arrange
        $payload = [];

        // Action
        $response = $this->postJson('/v1/offers', $payload);

        // Assert
        $response->assertUnauthorized();
    }

    public function test_should_return_403_for_refresh_token(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['refresh']);

        // Action
        $response = $this->postJson('/v1/offers', []);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseCount('offers', 0);
    }

    public function test_should_return_403_for_retailer_profile(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/offers', []);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseCount('offers', 0);
    }
}
