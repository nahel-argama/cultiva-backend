<?php

namespace Tests\Feature\domain\Models\Offer\Controllers\OfferController;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\AddressFactory;
use Database\Factories\CategoryFactory;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AvailableIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_list_only_active_offers_with_available_stock_in_newest_order(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $firstProducer = ProducerFactory::new()->create();
        $secondProducer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create();
        $olderCheapVisible = OfferFactory::new()->create([
            'producer_id' => $firstProducer->id,
            'category_id' => $category->id,
            'unit_price' => '1.00',
            'total_quantity' => 10,
            'reserved_quantity' => 2,
            'status' => OfferStatus::ACTIVE,
            'created_at' => now()->subMinute(),
        ]);
        $newerExpensiveVisible = OfferFactory::new()->create([
            'producer_id' => $secondProducer->id,
            'category_id' => $category->id,
            'unit_price' => '99.00',
            'total_quantity' => 20,
            'reserved_quantity' => 5,
            'status' => OfferStatus::ACTIVE,
            'created_at' => now(),
        ]);
        OfferFactory::new()->create([
            'producer_id' => $firstProducer->id,
            'category_id' => $category->id,
            'total_quantity' => 5,
            'reserved_quantity' => 5,
            'status' => OfferStatus::ACTIVE,
        ]);
        OfferFactory::new()->create([
            'producer_id' => $firstProducer->id,
            'category_id' => $category->id,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'status' => OfferStatus::INACTIVE,
        ]);
        OfferFactory::new()->create([
            'producer_id' => $firstProducer->id,
            'category_id' => $category->id,
            'total_quantity' => 5,
            'reserved_quantity' => 5,
            'status' => OfferStatus::INACTIVE,
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.id', $newerExpensiveVisible->id)
            ->assertJsonPath('data.0.available_quantity', 15)
            ->assertJsonPath('data.0.is_visible', true)
            ->assertJsonPath('data.1.id', $olderCheapVisible->id)
            ->assertJsonPath('data.1.available_quantity', 8)
            ->assertJsonMissingPath('data.0.producer_id');
        $this->assertCount(2, $response->json('data'));
    }

    public function test_should_search_only_visible_offers_before_pagination(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $matching = OfferFactory::new()->create([
            'product_name' => 'Tomate Cereja',
            'status' => OfferStatus::ACTIVE,
        ]);
        OfferFactory::new()->create(['product_name' => 'Batata', 'status' => OfferStatus::ACTIVE]);
        OfferFactory::new()->create(['product_name' => 'Tomate Inativo', 'status' => OfferStatus::INACTIVE]);
        OfferFactory::new()->create([
            'product_name' => 'Tomate Esgotado',
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 5,
            'reserved_quantity' => 5,
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers?search=tOMate&per_page=1');

        // Assert
        $response->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $matching->id);
    }

    public function test_should_return_401_without_token(): void
    {
        // Arrange
        $url = '/v1/offers';

        // Action
        $response = $this->getJson($url);

        // Assert
        $response->assertUnauthorized();
    }

    public function test_should_return_403_for_refresh_token(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['refresh']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertForbidden();
    }

    public function test_should_order_available_offers_by_distance_and_include_distance_km_in_response(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        AddressFactory::new()->create([
            'company_id' => $retailer->company->id,
            'coordinate' => 'POINT(-49.0629788 -26.9244749)',
        ]);

        $closerProducer = ProducerFactory::new()->create();
        AddressFactory::new()->create([
            'company_id' => $closerProducer->company->id,
            'coordinate' => 'POINT(-49.0700000 -26.9300000)',
        ]);
        $closerOffer = OfferFactory::new()->create([
            'producer_id' => $closerProducer->id,
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'created_at' => now()->subHours(2),
        ]);

        $furtherProducer = ProducerFactory::new()->create();
        AddressFactory::new()->create([
            'company_id' => $furtherProducer->company->id,
            'coordinate' => 'POINT(-49.1500000 -26.9900000)',
        ]);
        $furtherOffer = OfferFactory::new()->create([
            'producer_id' => $furtherProducer->id,
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'created_at' => now()->subHour(),
        ]);

        $noCoordinateProducer = ProducerFactory::new()->create();
        $noCoordinateOffer = OfferFactory::new()->create([
            'producer_id' => $noCoordinateProducer->id,
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'created_at' => now(),
        ]);

        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.id', $closerOffer->id)
            ->assertJsonPath('data.0.distance_km', 0.93)
            ->assertJsonPath('data.1.id', $furtherOffer->id)
            ->assertJsonPath('data.1.distance_km', 11.29)
            ->assertJsonPath('data.2.id', $noCoordinateOffer->id)
            ->assertJsonPath('data.2.distance_km', null);
    }

    public function test_should_return_null_distance_km_when_retailer_has_no_coordinates(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();

        $producer = ProducerFactory::new()->create();
        AddressFactory::new()->create([
            'company_id' => $producer->company->id,
            'coordinate' => 'POINT(-49.0700000 -26.9300000)',
        ]);
        $offer = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
        ]);

        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.id', $offer->id)
            ->assertJsonPath('data.0.distance_km', null);
    }

}
