<?php

namespace Tests\Unit\domain\Models\Wishlist\Actions;

use Cultiva\Models\Wishlist\Actions\GetWishlistAnalyticsAction;
use Cultiva\Models\Wishlist\DTO\GetWishlistAnalyticsDTO;
use Cultiva\Models\Wishlist\WishlistItem;
use Database\Factories\RetailerFactory;
use Tests\TestCase;

class GetWishlistAnalyticsActionTest extends TestCase
{
    public function test_should_calculate_rounded_percentage_and_position_for_grouped_items(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        WishlistItem::query()->insert([
            ['retailer_id' => $retailer->id, 'source_product_id' => 'product-1', 'product_name' => 'Tomate', 'state' => 'SP', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $sut = app(GetWishlistAnalyticsAction::class);

        // Action
        $result = $sut->execute($retailer->company->user, new GetWishlistAnalyticsDTO(state: null, limit: 10));

        // Assert
        $this->assertSame(1, $result->totalItems);
        $this->assertSame(100, $result->results[0]->percentage);
        $this->assertSame(1, $result->results[0]->position);
    }
}
