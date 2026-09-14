<?php

namespace Tests\Feature\domain\Models\Wishlist\Http\Controllers\WishlistController;

use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\WishlistItemFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_return_anonymous_ranked_analytics_for_retailer_with_top_three_first(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        WishlistItemFactory::new()->count(5)->create(['source_product_id' => 'product-1', 'product_name' => 'Tomate', 'state' => 'SP']);
        WishlistItemFactory::new()->count(3)->create(['source_product_id' => 'product-2', 'product_name' => 'Batata', 'state' => 'SP']);
        WishlistItemFactory::new()->count(2)->create(['source_product_id' => 'product-3', 'product_name' => 'Cenoura', 'state' => 'SP']);
        WishlistItemFactory::new()->count(2)->create(['source_product_id' => 'product-4', 'product_name' => 'Alface', 'state' => 'RJ']);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/wishlist/analytics');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.state', null)
            ->assertJsonPath('data.total_items', 12)
            ->assertJsonPath('data.results.0.position', 1)
            ->assertJsonPath('data.results.0.source_product_id', 'product-1')
            ->assertJsonPath('data.results.0.total', 5)
            ->assertJsonPath('data.results.0.percentage', 42)
            ->assertJsonPath('data.results.1.source_product_id', 'product-2')
            ->assertJsonMissingPath('data.results.0.retailer_id')
            ->assertJsonMissingPath('data.results.0.user_id');
    }

    public function test_should_filter_analytics_by_saved_state_for_producer(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        WishlistItemFactory::new()->count(3)->create(['source_product_id' => 'product-1', 'product_name' => 'Tomate', 'state' => 'SP']);
        WishlistItemFactory::new()->create(['source_product_id' => 'product-2', 'product_name' => 'Batata', 'state' => 'RJ']);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/wishlist/analytics?state=SP&limit=3');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.state', 'SP')
            ->assertJsonPath('data.total_items', 3)
            ->assertJsonPath('data.results.0.total', 3)
            ->assertJsonPath('data.results.0.percentage', 75)
            ->assertJsonMissingPath('data.results.1');
    }

    public function test_should_append_others_and_use_global_wishlist_total_for_percentages(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        WishlistItemFactory::new()->count(5)->create(['source_product_id' => 'product-1', 'product_name' => 'Tomate']);
        WishlistItemFactory::new()->count(3)->create(['source_product_id' => 'product-2', 'product_name' => 'Batata']);
        WishlistItemFactory::new()->count(2)->create(['source_product_id' => 'product-3', 'product_name' => 'Cenoura']);
        WishlistItemFactory::new()->count(2)->create(['source_product_id' => 'product-4', 'product_name' => 'Alface']);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/wishlist/analytics?limit=2');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.total_items', 12)
            ->assertJsonPath('data.results.0.percentage', 42)
            ->assertJsonPath('data.results.1.percentage', 25)
            ->assertJsonCount(2, 'data.results')
            ->assertJsonPath('data.others.total', 4)
            ->assertJsonPath('data.others.percentage', 33)
            ->assertJsonMissingPath('data.others.source_product_id')
            ->assertJsonMissingPath('data.others.product_name');
    }

    public function test_should_return_empty_analytics_when_filter_has_no_items(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/wishlist/analytics?state=SP');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.total_items', 0)
            ->assertJsonPath('data.results', [])
            ->assertJsonMissingPath('data.others');
    }
}
