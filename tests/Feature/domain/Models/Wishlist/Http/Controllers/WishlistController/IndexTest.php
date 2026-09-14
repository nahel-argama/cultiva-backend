<?php

namespace Tests\Feature\domain\Models\Wishlist\Http\Controllers\WishlistController;

use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Factories\WishlistItemFactory;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndexTest extends TestCase
{
    public function test_should_list_only_current_retailer_items_with_search_and_pagination(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        WishlistItemFactory::new()->createMany([
            ['retailer_id' => $retailer->id, 'product_name' => 'Tomate Italiano'],
            ['retailer_id' => $retailer->id, 'product_name' => 'Batata Doce'],
            ['retailer_id' => $retailer->id, 'product_name' => 'Tomate Cereja'],
            ['retailer_id' => $retailer->id, 'product_name' => 'Tangerina'],
            ['retailer_id' => $retailer->id, 'product_name' => 'Ação Orgânica'],
        ]);
        WishlistItemFactory::new()->create(['product_name' => 'Tomate de outro varejista']);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/wishlist/items?search=tang&page=1&per_page=1');

        // Assert
        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.product_name', 'Tangerina')
            ->assertJsonMissing(['product_name' => 'Tomate de outro varejista']);

        $accentInsensitiveResponse = $this->getJson('/v1/wishlist/items?search=acao');

        $accentInsensitiveResponse->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.product_name', 'Ação Orgânica');
    }

    public function test_should_forbid_producer_from_listing_wishlist(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/wishlist/items');

        // Assert
        $response->assertForbidden();
    }
}
