<?php

namespace Tests\Feature\domain\Models\Offer\Http\Controllers\OfferController;

use Cultiva\Models\Offer\Enums\OfferStatus;
use Database\Factories\CategoryFactory;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class IndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_list_only_own_offers_in_newest_order_including_hidden(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $otherProducer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create();
        $olderInactive = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'category_id' => $category->id,
            'unit_price' => '1.00',
            'status' => OfferStatus::INACTIVE,
            'created_at' => now()->subMinute(),
        ]);
        $newerExhausted = OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'category_id' => $category->id,
            'unit_price' => '99.00',
            'total_quantity' => 5,
            'reserved_quantity' => 5,
            'status' => OfferStatus::ACTIVE,
            'created_at' => now(),
        ]);
        OfferFactory::new()->create([
            'producer_id' => $otherProducer->id,
            'category_id' => $category->id,
        ]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers?per_page=2');

        // Assert
        $response->assertOk()
            ->assertJsonPath('data.0.id', $newerExhausted->id)
            ->assertJsonPath('data.0.is_visible', false)
            ->assertJsonPath('data.1.id', $olderInactive->id)
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 2)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('has_previous_page', false)
            ->assertJsonPath('has_next_page', false)
            ->assertJsonMissingPath('pagination')
            ->assertJsonMissingPath('meta');
        $this->assertCount(2, $response->json('data'));
    }

    public function test_should_use_default_pagination(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertOk()
            ->assertJsonPath('current_page', 1)
            ->assertJsonPath('per_page', 15)
            ->assertExactJsonStructure([
                'data',
                'current_page', 'per_page', 'total',
                'has_previous_page', 'has_next_page',
            ]);
    }

    #[DataProvider('paginationPagesProvider')]
    public function test_should_return_page_totals_and_navigation_flags(
        int $total,
        int $perPage,
        int $page,
        int $count,
        bool $previous,
        bool $next,
    ): void {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create();
        OfferFactory::new()->count($total)->create([
            'producer_id' => $producer->id,
            'category_id' => $category->id,
        ]);
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers?page='.$page.'&per_page='.$perPage);

        // Assert
        $response->assertOk()
            ->assertJsonCount($count, 'data')
            ->assertJsonPath('current_page', $page)
            ->assertJsonPath('per_page', $perPage)
            ->assertJsonPath('total', $total)
            ->assertJsonPath('has_previous_page', $previous)
            ->assertJsonPath('has_next_page', $next)
            ->assertExactJsonStructure([
                'data', 'current_page', 'per_page', 'total',
                'has_previous_page', 'has_next_page',
            ]);
    }

    public static function paginationPagesProvider(): array
    {
        return [
            'first' => [5, 2, 1, 2, false, true],
            'intermediate' => [5, 2, 2, 2, true, true],
            'last' => [5, 2, 3, 1, true, false],
            'single and maximum size' => [1, 100, 1, 1, false, false],
            'empty' => [0, 15, 1, 0, false, false],
            'beyond last' => [5, 2, 4, 0, true, false],
        ];
    }

    #[DataProvider('invalidPaginationProvider')]
    public function test_should_return_422_for_invalid_pagination(string $query): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/offers?'.$query);

        // Assert
        $response->assertUnprocessable();
    }

    public static function invalidPaginationProvider(): array
    {
        return [
            'page below one' => ['page=0'],
            'page not an integer' => ['page=first'],
            'per page not an integer' => ['per_page=many'],
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
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['refresh']);

        // Action
        $response = $this->getJson('/v1/offers');

        // Assert
        $response->assertForbidden();
    }

}
