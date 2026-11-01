<?php

namespace Tests\Feature\domain\Models\Purchase\Controllers\PurchaseController;

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

    public function test_should_search_products_before_pagination_without_leaking_other_accounts(): void
    {
        // Arrange
        $matching = PurchaseFactory::new()->create(['product_name' => 'Tomate Cereja']);
        $second = PurchaseFactory::new()->create([
            'producer_id' => $matching->producer_id,
            'product_name' => 'Tomate Italiano',
        ]);
        PurchaseFactory::new()->create(['producer_id' => $matching->producer_id, 'product_name' => 'Batata']);
        $foreign = PurchaseFactory::new()->create(['product_name' => 'Tomate Cereja']);
        Sanctum::actingAs($matching->producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/sales?search=tOMate&per_page=1&page=2');

        // Assert
        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $matching->id);
        $this->getJson('/v1/sales?search=abacaxi')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/v1/sales?search=')->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/v1/sales?search='.str_repeat('a', 256))
            ->assertUnprocessable()->assertJsonValidationErrors('search');
        $this->getJson('/v1/sales?search='.$second->id)->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $second->id);
        $this->getJson('/v1/sales?search='.$foreign->id)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_should_return_real_buyer_details_without_exposing_supplier_as_counterparty(): void
    {
        // Arrange
        $purchase = PurchaseFactory::new()->create();
        $retailer = $purchase->retailer;
        $company = $retailer->company;
        $address = $company->address;
        Sanctum::actingAs($purchase->producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/sales');

        // Assert
        $response->assertOk()->assertJsonPath('data.0.counterparty', [
            'id' => $retailer->id,
            'role' => 'retailer',
            'trade_name' => $company->trade_name,
            'legal_name' => $company->legal_name,
            'document_number' => $company->document_number,
            'name' => $company->user->name,
            'email' => $company->user->email,
            'phone' => $company->phone,
            'address' => [
                'zip' => $address->zip,
                'street' => $address->street,
                'number' => $address->number,
                'complement' => $address->complement,
                'reference_point' => $address->reference_point,
                'neighborhood' => $address->neighborhood,
                'city' => $address->city,
                'state' => $address->state,
            ],
        ])->assertJsonPath('data.0.category', [
            'id' => $purchase->offer->category->id,
            'name' => $purchase->offer->category->name,
        ]);
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
