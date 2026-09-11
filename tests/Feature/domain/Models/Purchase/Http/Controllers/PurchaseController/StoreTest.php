<?php

namespace Tests\Feature\domain\Models\Purchase\Http\Controllers\PurchaseController;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\DeliveryFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_partial_purchase_and_reduce_offer_availability(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'unit_price' => '10.00',
            'total_quantity' => 100,
            'reserved_quantity' => 0,
            'status' => OfferStatus::ACTIVE,
            'product_name' => 'Tomate Italiano',
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/offers/'.$offer->id.'/purchase', [
            'quantity' => 30,
        ]);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('data.offer_id', $offer->id)
            ->assertJsonPath('data.retailer_id', $retailer->id)
            ->assertJsonPath('data.producer_id', $offer->producer_id)
            ->assertJsonPath('data.product_name', 'Tomate Italiano')
            ->assertJsonPath('data.quantity', 30)
            ->assertJsonPath('data.unit_price', '10.00')
            ->assertJsonPath('data.total_price', '300.00');

        $this->assertDatabaseHas('purchases', [
            'offer_id' => $offer->id,
            'retailer_id' => $retailer->id,
            'producer_id' => $offer->producer_id,
            'quantity' => 30,
            'unit_price' => '10.00',
            'total_price' => '300.00',
        ]);
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'reserved_quantity' => 30,
            'status' => OfferStatus::ACTIVE->value,
        ]);
    }

    public function test_should_inactivate_offer_when_purchase_consumes_remaining_quantity(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'unit_price' => '12.50',
            'total_quantity' => 20,
            'reserved_quantity' => 5,
            'status' => OfferStatus::ACTIVE,
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/offers/'.$offer->id.'/purchase', [
            'quantity' => 15,
        ]);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('data.quantity', 15)
            ->assertJsonPath('data.total_price', '187.50');
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'reserved_quantity' => 20,
            'status' => OfferStatus::INACTIVE->value,
        ]);
    }

    #[DataProvider('invalidQuantityProvider')]
    public function test_should_reject_invalid_purchase_quantity_without_side_effects(
        mixed $quantity,
        int $status,
    ): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'total_quantity' => 10,
            'reserved_quantity' => 2,
            'status' => OfferStatus::ACTIVE,
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/offers/'.$offer->id.'/purchase', [
            'quantity' => $quantity,
        ]);

        // Assert
        $response->assertStatus($status);
        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'reserved_quantity' => 2,
            'status' => OfferStatus::ACTIVE->value,
        ]);
    }

    public static function invalidQuantityProvider(): array
    {
        return [
            'zero' => [0, 422],
            'negative' => [-1, 422],
            'fractional' => [1.5, 422],
            'above availability' => [9, 409],
        ];
    }

    public function test_should_reject_purchase_from_inactive_offer_without_side_effects(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'status' => OfferStatus::INACTIVE,
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/offers/'.$offer->id.'/purchase', [
            'quantity' => 1,
        ]);

        // Assert
        $response->assertStatus(409);
        $this->assertDatabaseCount('purchases', 0);
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'reserved_quantity' => 0,
            'status' => OfferStatus::INACTIVE->value,
        ]);
    }

    public function test_should_reject_purchase_for_producer_and_delivery_profiles(): void
    {
        // Arrange
        $offer = OfferFactory::new()->create([
            'total_quantity' => 10,
            'status' => OfferStatus::ACTIVE,
        ]);
        $producer = ProducerFactory::new()->create();
        $delivery = DeliveryFactory::new()->create();

        // Action & Assert
        Sanctum::actingAs($producer->company->user, ['access']);
        $this->postJson('/v1/offers/'.$offer->id.'/purchase', ['quantity' => 1])->assertForbidden();
        Sanctum::actingAs($delivery->company->user, ['access']);
        $this->postJson('/v1/offers/'.$offer->id.'/purchase', ['quantity' => 1])->assertForbidden();
        $this->assertDatabaseCount('purchases', 0);
    }

    public function test_should_reject_unauthenticated_purchase(): void
    {
        // Arrange
        $offer = OfferFactory::new()->create(['status' => OfferStatus::ACTIVE]);

        // Action & Assert
        $this->postJson('/v1/offers/'.$offer->id.'/purchase', ['quantity' => 1])->assertUnauthorized();
    }

    public function test_should_allow_only_one_purchase_to_consume_the_last_available_units(): void
    {
        // Arrange
        $firstRetailer = RetailerFactory::new()->create();
        $secondRetailer = RetailerFactory::new()->create();
        $offer = OfferFactory::new()->create([
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'status' => OfferStatus::ACTIVE,
        ]);

        // Action
        Sanctum::actingAs($firstRetailer->company->user, ['access']);
        $firstResponse = $this->postJson('/v1/offers/'.$offer->id.'/purchase', ['quantity' => 10]);
        Sanctum::actingAs($secondRetailer->company->user, ['access']);
        $secondResponse = $this->postJson('/v1/offers/'.$offer->id.'/purchase', ['quantity' => 10]);

        // Assert
        $firstResponse->assertCreated();
        $secondResponse->assertStatus(409);
        $this->assertDatabaseCount('purchases', 1);
        $this->assertDatabaseHas('offers', [
            'id' => $offer->id,
            'reserved_quantity' => 10,
            'status' => OfferStatus::INACTIVE->value,
        ]);
    }
}
