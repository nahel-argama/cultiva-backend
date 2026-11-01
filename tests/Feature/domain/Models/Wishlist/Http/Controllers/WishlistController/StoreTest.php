<?php

namespace Tests\Feature\domain\Models\Wishlist\Http\Controllers\WishlistController;

use Cultiva\Models\Offer\Jobs\PopulateProductImageJob;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\WishlistItemFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreTest extends TestCase
{
    public function test_should_return_not_found_without_creating_wishlist_item_when_product_source_returns_404(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
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
            $response->assertStatus(409)
                ->assertJsonPath('message', Lang::get('wishlist.in_progress'));
            Http::assertNothingSent();
            $this->assertDatabaseCount('wishlist_items', 0);
        } finally {
            $lock->release();
        }
    }

    public function test_should_hold_lock_while_waiting_for_product_service_response(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $lockWasHeldDuringLookup = false;
        Http::fake([
            '*/products/product-123' => function () use ($retailer, &$lockWasHeldDuringLookup) {
                $lockWasHeldDuringLookup = ! Cache::lock("wishlist:{$retailer->id}:product-123", 60)->get();

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
        $response->assertCreated();
        $this->assertTrue($lockWasHeldDuringLookup);
        $this->assertTrue(Cache::lock("wishlist:{$retailer->id}:product-123", 60)->get(fn() => true));
    }

    public function test_should_return_conflict_when_product_is_added_during_product_lookup(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
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
        $state = $retailer->company->address->state;
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
            ->assertJsonPath('data.state', $state);
        $this->assertDatabaseHas('wishlist_items', [
            'retailer_id' => $retailer->id,
            'source_product_id' => 'product-123',
            'product_name' => 'Tomate',
            'state' => $state,
        ]);
        $this->assertTrue(Cache::lock("wishlist:{$retailer->id}:product-123", 60)->get(fn() => true));
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
        $this->assertTrue(Cache::lock("wishlist:{$retailer->id}:product-123", 60)->get(fn() => true));
    }

    public function test_should_dispatch_populate_product_image_job_and_include_image_url_on_wishlist_item_creation(): void
    {
        // Arrange
        Queue::fake();
        Storage::fake('public');
        $retailer = RetailerFactory::new()->create();
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
            ->assertJsonPath('data.image_url', Storage::disk('public')->url('products/product-123.jpg'));

        Queue::assertPushed(PopulateProductImageJob::class, function (PopulateProductImageJob $job) {
            return $job->sourceProductId === 'product-123' && $job->productName === 'Tomate';
        });
    }
}
