<?php

namespace Tests\Feature\domain\Models\Offer\Controllers\OfferController;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\CategoryFactory;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_update_own_offer_and_ignore_product_and_status(): void
    {
        // Arrange
        Http::fake();
        $producer = ProducerFactory::new()->create();
        $originalCategory = CategoryFactory::new()->create();
        $newCategory = CategoryFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'category_id' => $originalCategory->id,
            'source_product_id' => '1',
            'product_name' => 'Nome original',
            'total_quantity' => 10,
            'reserved_quantity' => 2,
            'status' => OfferStatus::ACTIVE,
            'unit_price' => '10.00',
        ]);
        Sanctum::actingAs($producer->company->user, ['access']);
        $payload = [
            'source_product_id' => '0002',
            'status' => 'inactive',
            'category_id' => $newCategory->id,
            'unit_price' => '25.90',
            'total_quantity' => 20,
            'producer_id' => 999,
            'reserved_quantity' => 99,
        ];

        // Action
        $response = $this->patchJson('/v1/offers/'.$offer->id, $payload);

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.source_product_id', '1')
            ->assertJsonPath('data.product_name', 'Nome original')
            ->assertJsonPath('data.category.id', $newCategory->id)
            ->assertJsonPath('data.unit_price', '25.90')
            ->assertJsonPath('data.total_quantity', 20)
            ->assertJsonPath('data.reserved_quantity', 2)
            ->assertJsonPath('data.status', 'active');
        Http::assertNothingSent();
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'producer_id' => $producer->id,
            'source_product_id' => '1',
            'product_name' => 'Nome original',
            'category_id' => $newCategory->id,
            'unit_price' => '25.90',
            'total_quantity' => 20,
            'reserved_quantity' => 2,
            'status' => 'active',
        ]);
    }

    #[DataProvider('invalidPayloadProvider')]
    public function test_should_return_422_for_invalid_update_payload(array $payload): void
    {
        // Arrange
        Http::fake();
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

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
            'only source product id' => [['source_product_id' => '0002']],
            'only status' => [['status' => 'inactive']],
            'invalid category id' => [['category_id' => 'one']],
            'invalid price' => [['unit_price' => '0.00']],
            'invalid total' => [['total_quantity' => -1]],
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
        Sanctum::actingAs($producer->company->user, ['access']);

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
        Sanctum::actingAs($producer->company->user, ['access']);

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


    public function test_should_return_404_without_calling_product_source_for_another_producers_offer(): void
    {
        // Arrange
        Http::fake();
        $producer = ProducerFactory::new()->create();
        $otherProducer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $otherProducer->id]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/offers/'.$offer->id, [
            'unit_price' => '20.00',
        ]);

        // Assert
        $response->assertNotFound();
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
        Sanctum::actingAs($producer->company->user, ['refresh']);

        // Action
        $response = $this->patchJson('/v1/offers/1', []);

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_403_for_retailer_profile(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->patchJson('/v1/offers/1', []);

        // Assert
        $response->assertForbidden();
    }
}
