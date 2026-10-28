<?php

namespace Tests\Feature\domain\Models\Purchase\Controllers\PurchaseController;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\AddressFactory;
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

    public function test_should_search_products_before_pagination_without_leaking_other_accounts(): void
    {
        // Arrange
        $matching = PurchaseFactory::new()->create(['product_name' => 'Tomate Cereja']);
        $second = PurchaseFactory::new()->create([
            'retailer_id' => $matching->retailer_id,
            'product_name' => 'Tomate Italiano',
        ]);
        PurchaseFactory::new()->create(['retailer_id' => $matching->retailer_id, 'product_name' => 'Batata']);
        $foreign = PurchaseFactory::new()->create(['product_name' => 'Tomate Cereja']);
        Sanctum::actingAs($matching->retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/purchases?search=tOMate&per_page=1&page=2');

        // Assert
        $response->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.id', $matching->id);
        $this->getJson('/v1/purchases?search=abacaxi')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/v1/purchases?search=')->assertOk()->assertJsonPath('meta.total', 3);
        $this->getJson('/v1/purchases?search='.str_repeat('a', 256))
            ->assertUnprocessable()->assertJsonValidationErrors('search');
        $this->getJson('/v1/purchases?search='.$second->id)->assertOk()
            ->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.id', $second->id);
        $this->getJson('/v1/purchases?search='.$foreign->id)->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_should_return_real_supplier_details_and_preserve_purchase_snapshot(): void
    {
        // Arrange
        $purchase = PurchaseFactory::new()->create([
            'product_name' => 'Tomate comprado',
            'quantity' => 2,
            'unit_price' => '13.00',
            'total_price' => '26.00',
        ]);
        $producer = $purchase->producer;
        $company = $producer->company;
        $address = AddressFactory::new()->create(['company_id' => $company->id]);
        $category = $purchase->offer->category;
        $purchase->offer->update(['product_name' => 'Nome atualizado', 'unit_price' => '99.00']);
        Sanctum::actingAs($purchase->retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/purchases');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.counterparty', [
                'id' => $producer->id,
                'role' => 'producer',
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
            ])
            ->assertJsonPath('data.0.category', ['id' => $category->id, 'name' => $category->name])
            ->assertJsonPath('data.0.product_name', 'Tomate comprado')
            ->assertJsonPath('data.0.quantity', 2)
            ->assertJsonPath('data.0.unit_price', '13.00')
            ->assertJsonPath('data.0.total_price', '26.00');
        $this->assertDatabaseHas('purchases', ['id' => $purchase->id, 'product_name' => 'Tomate comprado']);
    }

    public function test_should_allow_missing_supplier_address_and_soft_deleted_company(): void
    {
        // Arrange
        $purchase = PurchaseFactory::new()->create();
        Sanctum::actingAs($purchase->retailer->company->user, ['access']);

        // Action & Assert
        $this->getJson('/v1/purchases')->assertOk()
            ->assertJsonPath('data.0.counterparty.id', $purchase->producer_id)
            ->assertJsonPath('data.0.counterparty.address', null);
        $purchase->producer->company->delete();
        $this->getJson('/v1/purchases')->assertOk()->assertJsonPath('data.0.counterparty', null);
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
