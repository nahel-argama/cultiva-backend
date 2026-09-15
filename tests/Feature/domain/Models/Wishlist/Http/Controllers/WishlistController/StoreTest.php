<?php

namespace Tests\Feature\domain\Models\Wishlist\Http\Controllers\WishlistController;

use Cultiva\Models\Address\Address;
use Database\Factories\AddressFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\WishlistItemFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreTest extends TestCase
{
    public function test_should_return_not_found_without_creating_wishlist_item_when_product_source_returns_404(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        AddressFactory::new()->create([
            'addressable_type' => $retailer->company::class,
            'addressable_id' => $retailer->company->id,
            'state' => 'SP',
        ]);
        Http::fake([
            '*/products/product-123' => Http::response(status: 404),
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/wishlist/items', ['product_id' => 'product-123']);

        // Assert
        $response->assertNotFound()
            ->assertJsonPath('message', Lang::get('wishlist.product_not_found'));
        $this->assertDatabaseCount('wishlist_items', 0);
    }

    public function test_should_return_conflict_when_product_lock_is_held(): void
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
        $lock = Cache::lock("wishlist:{$retailer->id}:product-123", 60);
        $this->assertTrue($lock->get());

        try {
            // Action
            $response = $this->postJson('/v1/wishlist/items', ['product_id' => 'product-123']);

            // Assert
            $response->assertStatus(409);
            $this->assertDatabaseCount('wishlist_items', 0);
            $this->assertTrue($lock->isOwnedByCurrentProcess());
        } finally {
            $lock->release();
        }
    }

    public function test_should_return_conflict_when_product_is_added_during_product_lookup(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        AddressFactory::new()->create([
            'addressable_type' => $retailer->company::class,
            'addressable_id' => $retailer->company->id,
            'state' => 'SP',
        ]);
        Http::fake([
            '*/products/product-123' => function () use ($retailer) {
                WishlistItemFactory::new()->create([
                    'retailer_id' => $retailer->id,
                    'source_product_id' => 'product-123',
                    'product_name' => 'Original name',
                    'state' => 'RJ',
                ]);

                return Http::response([
                    'id' => 'product-123',
                    'name' => 'Tomate',
                    'normal_name' => 'Tomate',
                ], 200);
            },
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->postJson('/v1/wishlist/items', ['product_id' => 'product-123']);

        // Assert
        $response->assertStatus(409)
            ->assertJsonPath('message', Lang::get('wishlist.already_exists'));
        $this->assertDatabaseCount('wishlist_items', 1);
        $this->assertDatabaseHas('wishlist_items', [
            'retailer_id' => $retailer->id,
            'source_product_id' => 'product-123',
            'product_name' => 'Original name',
            'state' => 'RJ',
        ]);
    }

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
        $this->assertTrue(Cache::lock("wishlist:{$retailer->id}:product-123", 60)->get(fn () => true));
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
        $this->assertTrue(Cache::lock("wishlist:{$retailer->id}:product-123", 60)->get(fn () => true));
    }
}
