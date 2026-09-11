<?php

namespace Tests\Feature\domain\Models\Purchase\Http\Controllers\PurchaseController;

use Database\Factories\PurchaseFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SaleIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_list_only_sales_from_the_authenticated_producer(): void
    {
        // Arrange
        $ownPurchase = PurchaseFactory::new()->create();
        PurchaseFactory::new()->create();
        Sanctum::actingAs($ownPurchase->producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/sales?per_page=10');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.id', $ownPurchase->id)
            ->assertJsonPath('data.0.producer_id', $ownPurchase->producer_id)
            ->assertJsonPath('data.0.retailer_id', $ownPurchase->retailer_id)
            ->assertJsonStructure(['data', 'links', 'meta']);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_should_return_403_for_retailer_accessing_sales(): void
    {
        // Arrange
        $purchase = PurchaseFactory::new()->create();
        Sanctum::actingAs($purchase->retailer->company->user, ['access']);

        // Action & Assert
        $this->getJson('/v1/sales')->assertForbidden();
    }
}
