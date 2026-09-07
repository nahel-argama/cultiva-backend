<?php

namespace Tests\Feature\domain\Models\Offer\Http\Controllers\OfferController;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\CategoryFactory;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AvailableIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_list_only_active_offers_with_available_stock_in_newest_order(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $firstProducer = ProducerFactory::new()->create();
        $secondProducer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create();
        $olderCheapVisible = OfferFactory::new()->create([
            'producer_id' => $firstProducer->id,
            'category_id' => $category->id,
            'unit_price' => '1.00',
            'total_quantity' => 10,
            'reserved_quantity' => 2,
            'status' => OfferStatus::ACTIVE,
            'created_at' => now()->subMinute(),
        ]);
        $newerExpensiveVisible = OfferFactory::new()->create([
            'producer_id' => $secondProducer->id,
            'category_id' => $category->id,
            'unit_price' => '99.00',
            'total_quantity' => 20,
            'reserved_quantity' => 5,
            'status' => OfferStatus::ACTIVE,
            'created_at' => now(),
        ]);
        OfferFactory::new()->create([
            'producer_id' => $firstProducer->id,
            'category_id' => $category->id,
            'total_quantity' => 5,
            'reserved_quantity' => 5,
            'status' => OfferStatus::ACTIVE,
        ]);
        OfferFactory::new()->create([
            'producer_id' => $firstProducer->id,
            'category_id' => $category->id,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
            'status' => OfferStatus::INACTIVE,
        ]);
        OfferFactory::new()->create([
            'producer_id' => $firstProducer->id,
            'category_id' => $category->id,
            'total_quantity' => 5,
            'reserved_quantity' => 5,
            'status' => OfferStatus::INACTIVE,
        ]);
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers?per_page=10');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.id', $newerExpensiveVisible->id)
            ->assertJsonPath('data.0.available_quantity', 15)
            ->assertJsonPath('data.0.is_visible', true)
            ->assertJsonPath('data.1.id', $olderCheapVisible->id)
            ->assertJsonPath('data.1.available_quantity', 8)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 10)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('has_previous_page', false)
            ->assertJsonPath('has_next_page', false)
            ->assertJsonMissingPath('pagination')
            ->assertJsonMissingPath('meta')
            ->assertJsonMissingPath('data.0.producer_id')
            ->assertExactJsonStructure([
                'data', 'current_page', 'per_page', 'total',
                'has_previous_page', 'has_next_page',
            ]);
        $this->assertCount(2, $response->json('data'));
    }

    #[DataProvider('invalidPaginationProvider')]
    public function test_should_return_422_for_invalid_pagination(string $query): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers?'.$query);

        // Assert
        $response->assertUnprocessable();
    }

    public static function invalidPaginationProvider(): array
    {
        return [
            'page below one' => ['page=0'],
            'per page below one' => ['per_page=0'],
            'per page above maximum' => ['per_page=101'],
        ];
    }

    public function test_should_return_401_without_token(): void
    {
        // Arrange
        $url = '/v1/offers';

        // Action
        $response = $this->getJson($url);

        // Assert
        $response->assertUnauthorized();
    }

    public function test_should_return_403_for_refresh_token(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['refresh']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertForbidden();
    }

}
