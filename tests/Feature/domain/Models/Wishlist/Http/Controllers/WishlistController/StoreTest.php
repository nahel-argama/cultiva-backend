<?php

namespace Tests\Feature\domain\Models\Wishlist\Http\Controllers\WishlistController;

use Cultiva\Models\Address\Address;
use Database\Factories\AddressFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreTest extends TestCase
{
    public function test_should_create_wishlist_item_from_product_id_with_official_name_and_company_state(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        AddressFactory::new()->create([
            'addressable_type' => $retailer->company::class,
            'addressable_id' => $retailer->company->id,
            'state' => 'SP',
        ]);
        Http::fake([
            '*/products/product-123' => Http::response([
                'id' => 'product-123',
                'name' => 'Tomate',
                'normal_name' => 'Tomate',
            ], 200),
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/wishlist/items', [
            'product_id' => 'product-123',
        ]);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('data.source_product_id', 'product-123')
            ->assertJsonPath('data.product_name', 'Tomate')
            ->assertJsonPath('data.state', 'SP');
        $this->assertDatabaseHas('wishlist_items', [
            'retailer_id' => $retailer->id,
            'source_product_id' => 'product-123',
            'product_name' => 'Tomate',
            'state' => 'SP',
        ]);
    }

    public function test_should_reject_wishlist_item_for_producer(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/wishlist/items', [
            'product_id' => 'product-123',
        ]);

        // Assert
        $response->assertForbidden();
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_should_reject_wishlist_item_when_retailer_has_no_address(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/wishlist/items', [
            'product_id' => 'product-123',
        ]);

        // Assert
        $response->assertStatus(422);
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_should_reject_duplicate_product_for_same_retailer(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Address::query()->create([
            'addressable_type' => $retailer->company::class,
            'addressable_id' => $retailer->company->id,
            'zip' => '01001000',
            'street' => 'Rua A',
            'number' => '1',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
        Http::fake([
            '*/products/product-123' => Http::response([
                'id' => 'product-123',
                'name' => 'Tomate',
                'normal_name' => 'Tomate',
            ], 200),
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $firstResponse = $this->postJson('/v1/wishlist/items', ['product_id' => 'product-123']);
        $secondResponse = $this->postJson('/v1/wishlist/items', ['product_id' => 'product-123']);

        // Assert
        $firstResponse->assertCreated();
        $secondResponse->assertStatus(409);
        $this->assertDatabaseCount('wishlist_items', 1);
    }
}
