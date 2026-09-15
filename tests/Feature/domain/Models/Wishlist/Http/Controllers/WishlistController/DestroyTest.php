<?php

namespace Tests\Feature\domain\Models\Wishlist\Http\Controllers\WishlistController;

use Database\Factories\RetailerFactory;
use Database\Factories\WishlistItemFactory;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DestroyTest extends TestCase
{
    public function test_should_remove_item_owned_by_current_retailer(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $item = WishlistItemFactory::new()->create(['retailer_id' => $retailer->id]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->deleteJson('/v1/wishlist/items/'.$item->id);

        // Assert
        $response->assertNoContent();
        $this->assertDatabaseMissing('wishlist_items', ['id' => $item->id]);
    }

    public function test_should_not_remove_item_owned_by_another_retailer(): void
    {
        // Arrange
        $currentRetailer = RetailerFactory::new()->create();
        $otherItem = WishlistItemFactory::new()->create();
        Sanctum::actingAs($currentRetailer->company->user, ['access']);

        // Action
        $response = $this->deleteJson('/v1/wishlist/items/'.$otherItem->id);

        // Assert
        $response->assertNotFound();
        $this->assertDatabaseHas('wishlist_items', ['id' => $otherItem->id]);
    }
}
