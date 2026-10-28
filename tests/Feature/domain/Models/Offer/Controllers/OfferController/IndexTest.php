<?php

namespace Tests\Feature\domain\Models\Offer\Controllers\OfferController;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\CategoryFactory;
use Database\Factories\DeliveryFactory;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_list_only_own_offers_in_newest_order_including_hidden(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $otherProducer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create();
        $olderInactive = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'category_id' => $category->id,
            'unit_price' => '1.00',
            'status' => OfferStatus::INACTIVE,
            'created_at' => now()->subMinute(),
        ]);
        $newerExhausted = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'category_id' => $category->id,
            'unit_price' => '99.00',
            'total_quantity' => 5,
            'reserved_quantity' => 5,
            'status' => OfferStatus::ACTIVE,
            'created_at' => now(),
        ]);
        OfferFactory::new()->create([
            'producer_id' => $otherProducer->id,
            'category_id' => $category->id,
        ]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.id', $newerExhausted->id)
            ->assertJsonPath('data.0.is_visible', false)
            ->assertJsonPath('data.1.id', $olderInactive->id);
        $this->assertCount(2, $response->json('data'));
    }

    public function test_should_search_products_before_pagination_without_leaking_other_accounts(): void
    {
        // Arrange
        $matching = OfferFactory::new()->create(['product_name' => 'Tomate Cereja']);
        OfferFactory::new()->create([
            'producer_id' => $matching->producer_id,
            'product_name' => 'Tomate Italiano',
        ]);
        OfferFactory::new()->create(['producer_id' => $matching->producer_id, 'product_name' => 'Batata']);
        OfferFactory::new()->create(['product_name' => 'Tomate Cereja']);
        Sanctum::actingAs($matching->producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers?search=tOMate&per_page=1&page=2');

        // Assert
        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $matching->id);
        $this->getJson('/v1/offers?search=abacaxi')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/v1/offers?search=')->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/v1/offers?search='.str_repeat('a', 256))
            ->assertUnprocessable()->assertJsonValidationErrors('search');
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
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['refresh']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_403_for_delivery_profile(): void
    {
        // Arrange
        $delivery = DeliveryFactory::new()->create();
        Sanctum::actingAs($delivery->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertForbidden();
    }
}
