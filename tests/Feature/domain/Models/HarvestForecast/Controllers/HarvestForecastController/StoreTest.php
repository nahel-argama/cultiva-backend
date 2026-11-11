<?php

namespace Tests\Feature\domain\Models\HarvestForecast\Controllers\HarvestForecastController;

use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_future_forecast_without_changing_stock(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_success.php');
        Http::fake([
            'http://product-source.test/api/products/0002' => Http::response($fixture, 200),
        ]);
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);
        $startDate = now()->addDays(10)->toDateString();
        $payload = [
            'source_product_id' => '0002',
            'quantity' => 300,
            'start_date' => $startDate,
            'end_date' => $startDate,
        ];

        // Action
        $response = $this->postJson('/v1/harvest-forecasts', $payload);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('data.product.source_product_id', '0002')
            ->assertJsonPath('data.product.name', 'Tomate Italiano')
            ->assertJsonPath('data.quantity', 300)
            ->assertJsonPath('data.start_date', $startDate)
            ->assertJsonPath('data.end_date', $startDate);
        $this->assertDatabaseHas('harvest_forecasts', [
            'producer_id' => $producer->id,
            'source_product_id' => '0002',
            'product_name' => 'Tomate Italiano',
            'quantity' => 300,
            'start_date' => $startDate,
            'end_date' => $startDate,
        ]);
        $this->assertDatabaseCount('offers', 0);
    }

    public function test_should_reject_invalid_forecast_without_calling_product_source(): void
    {
        // Arrange
        Http::fake();
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/harvest-forecasts', [
            'source_product_id' => '0002',
            'quantity' => 0,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
        $this->assertDatabaseCount('harvest_forecasts', 0);
        Http::assertNothingSent();
    }

    public function test_should_reject_forecast_creation_for_retailer_profile(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/harvest-forecasts', []);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseCount('harvest_forecasts', 0);
    }

    public function test_should_not_create_forecast_when_source_product_is_missing(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        Http::fake([
            'http://product-source.test/api/products/missing' => Http::response(['detail' => 'Product not found'], 404),
        ]);
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/harvest-forecasts', [
            'source_product_id' => 'missing',
            'quantity' => 300,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
        ]);

        // Assert
        $response->assertUnprocessable();
        $this->assertDatabaseCount('harvest_forecasts', 0);
    }
}
