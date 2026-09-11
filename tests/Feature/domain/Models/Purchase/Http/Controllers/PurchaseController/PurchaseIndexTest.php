<?php

namespace Tests\Feature\domain\Models\Purchase\Http\Controllers\PurchaseController;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\OfferFactory;
use Database\Factories\PurchaseFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PurchaseIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_list_only_the_authenticated_retailers_purchases_with_pagination(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $otherRetailer = RetailerFactory::new()->create();
        $ownPurchase = PurchaseFactory::new()->create([
            'retailer_id' => $retailer->id,
            'quantity' => 3,
            'unit_price' => '10.00',
            'total_price' => '30.00',
        ]);
        PurchaseFactory::new()->create(['retailer_id' => $otherRetailer->id]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/purchases?per_page=10');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.id', $ownPurchase->id)
            ->assertJsonPath('data.0.quantity', 3)
            ->assertJsonPath('data.0.unit_price', '10.00')
            ->assertJsonPath('data.0.total_price', '30.00')
            ->assertJsonStructure(['data', 'links', 'meta']);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_should_return_403_for_non_retailer_profiles(): void
    {
        // Arrange
        $producerOffer = OfferFactory::new()->create(['status' => OfferStatus::ACTIVE]);
        Sanctum::actingAs($producerOffer->producer->company->user, ['access']);

        // Action & Assert
        $this->getJson('/v1/purchases')->assertForbidden();
    }
}
