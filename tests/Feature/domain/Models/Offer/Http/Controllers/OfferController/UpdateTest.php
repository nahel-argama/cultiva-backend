<?php

namespace Tests\Feature\domain\Models\Offer\Http\Controllers\OfferController;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\CategoryFactory;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_update_own_offer_and_refresh_product_snapshot(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_success.php');
        Http::fake([
            'http://product-source.test/api/products/0002' => Http::response($fixture, 200),
        ]);
        $producer = ProducerFactory::new()->create();
        $originalCategory = CategoryFactory::new()->create();
        $newCategory = CategoryFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'category_id' => $originalCategory->id,
            'source_product_id' => '1',
            'product_name' => 'Nome antigo',
            'total_quantity' => 10,
            'reserved_quantity' => 2,
        ]);
        Sanctum::actingAs($producer->user, ['access']);
        $payload = [
            'source_product_id' => '0002',
            'category_id' => $newCategory->id,
            'unit_price' => '25.90',
            'total_quantity' => 20,
            'status' => 'active',
        ];

        // Action
        $response = $this->patchJson('/v1/offers/'.$offer->id, $payload);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.source_product_id', '0002')
            ->assertJsonPath('data.product_name', 'Tomate Italiano')
            ->assertJsonPath('data.category.id', $newCategory->id)
            ->assertJsonPath('data.unit_price', '25.90')
            ->assertJsonPath('data.total_quantity', 20)
            ->assertJsonPath('data.reserved_quantity', 2)
            ->assertJsonPath('data.status', 'active');
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'producer_id' => $producer->id,
            'source_product_id' => '0002',
            'product_name' => 'Tomate Italiano',
            'category_id' => $newCategory->id,
            'unit_price' => '25.90',
            'total_quantity' => 20,
            'reserved_quantity' => 2,
            'status' => 'active',
        ]);
    }

    public function test_should_revalidate_current_product_when_source_id_is_omitted(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        Http::fake([
            'http://product-source.test/api/products/2' => Http::response([
                'id' => '2',
                'name' => 'Tomate atualizado',
                'normal_name' => 'tomate',
                'created_at' => '2026-09-05T12:00:00Z',
            ], 200),
        ]);
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'source_product_id' => '2',
            'product_name' => 'Tomate antigo',
        ]);
        Sanctum::actingAs($producer->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/offers/'.$offer->id, [
            'unit_price' => '15.00',
        ]);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.source_product_id', '2')
            ->assertJsonPath('data.product_name', 'Tomate atualizado')
            ->assertJsonPath('data.unit_price', '15.00');
        Http::assertSentCount(1);
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'product_name' => 'Tomate atualizado',
            'unit_price' => '15.00',
        ]);
    }

    #[DataProvider('invalidPayloadProvider')]
    public function test_should_return_422_for_invalid_update_payload(array $payload): void
    {
        // Arrange
        Http::fake();
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        Sanctum::actingAs($producer->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/offers/'.$offer->id, $payload);

        // Assert
        $response->assertUnprocessable();
        Http::assertNothingSent();
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'producer_id' => $producer->id,
            'reserved_quantity' => $offer->reserved_quantity,
        ]);
    }

    public static function invalidPayloadProvider(): array
    {
        return [
            'empty payload' => [[]],
            'producer supplied by client' => [['producer_id' => 999]],
            'reserved quantity supplied by client' => [['reserved_quantity' => 1]],
            'invalid source id' => [['source_product_id' => '']],
            'invalid category id' => [['category_id' => 'one']],
            'invalid price' => [['unit_price' => '0.00']],
            'invalid total' => [['total_quantity' => -1]],
            'invalid status' => [['status' => 'draft']],
        ];
    }

    public function test_should_return_422_without_changes_when_total_is_below_reserved(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'total_quantity' => 10,
            'reserved_quantity' => 5,
        ]);
        Sanctum::actingAs($producer->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/offers/'.$offer->id, [
            'total_quantity' => 4,
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonPath('message', Lang::get('offers.invalid_stock'));
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'total_quantity' => 10,
            'reserved_quantity' => 5,
        ]);
    }

    public function test_should_return_422_without_changes_when_category_does_not_exist(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        Sanctum::actingAs($producer->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/offers/'.$offer->id, [
            'category_id' => 999999,
        ]);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonPath('message', Lang::get('offers.category_not_found'));
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'category_id' => $offer->category_id,
        ]);
    }

    public function test_should_return_422_without_changes_when_product_does_not_exist(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        Http::fake([
            'http://product-source.test/api/products/99' => Http::response(['detail' => 'Product not found'], 404),
        ]);
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'source_product_id' => '2',
            'product_name' => 'Tomate',
        ]);
        Sanctum::actingAs($producer->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/offers/'.$offer->id, [
            'source_product_id' => '99',
        ]);

        // Assert
        $response->assertUnprocessable();
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'source_product_id' => '2',
            'product_name' => 'Tomate',
        ]);
    }

    public function test_should_return_503_without_changes_when_product_source_is_unavailable(): void
    {
        // Arrange
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        Http::fake([
            'http://product-source.test/api/products/2' => fn () => throw new ConnectionException('Connection timed out'),
        ]);
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'source_product_id' => '2',
            'unit_price' => '10.00',
        ]);
        Sanctum::actingAs($producer->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/offers/'.$offer->id, [
            'unit_price' => '20.00',
        ]);

        // Assert
        $response->assertServiceUnavailable();
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'unit_price' => '10.00',
        ]);
    }

    public function test_should_return_404_without_calling_product_source_for_another_producers_offer(): void
    {
        // Arrange
        Http::fake();
        $producer = ProducerFactory::new()->create();
        $otherProducer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $otherProducer->id]);
        Sanctum::actingAs($producer->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/offers/'.$offer->id, [
            'unit_price' => '20.00',
        ]);

        // Assert
        $response->assertNotFound()
            ->assertJsonPath('message', Lang::get('offers.not_found'));
        Http::assertNothingSent();
    }

    public function test_should_return_401_without_token(): void
    {
        // Arrange
        $url = '/v1/offers/1';

        // Action
        $response = $this->patchJson($url, []);

        // Assert
        $response->assertUnauthorized();
    }

    public function test_should_return_403_for_refresh_token(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->user, ['refresh']);

        // Action
        $response = $this->patchJson('/v1/offers/1', []);

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_403_for_retailer_profile(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/offers/1', []);

        // Assert
        $response->assertForbidden();
    }
}
