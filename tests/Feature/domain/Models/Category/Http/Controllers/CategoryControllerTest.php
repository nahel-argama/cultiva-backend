<?php

namespace Tests\Feature\domain\Models\Category\Http\Controllers;

use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_list_exact_seeded_categories_for_producer(): void
    {
        // Arrange
        $this->seed(CategorySeeder::class);
        $this->seed(CategorySeeder::class);
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/products/categories');

        // Assert
        $response->assertOk()->assertExactJson([
            'data' => [
                ['id' => 1, 'name' => 'Frutas'],
                ['id' => 2, 'name' => 'Legumes'],
                ['id' => 3, 'name' => 'Verduras'],
                ['id' => 4, 'name' => 'Tubérculos e raízes'],
                ['id' => 5, 'name' => 'Grãos e cereais'],
            ],
        ]);
        $this->assertDatabaseCount('categories', 5);
    }

    public function test_should_return_401_without_token(): void
    {
        // Arrange
        $this->seed(CategorySeeder::class);

        // Action
        $response = $this->getJson('/v1/products/categories');

        // Assert
        $response->assertUnauthorized();
    }

    public function test_should_return_403_for_refresh_token(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        Sanctum::actingAs($producer->company->user, ['refresh']);

        // Action
        $response = $this->getJson('/v1/products/categories');

        // Assert
        $response->assertForbidden();
    }

    public function test_should_return_403_for_retailer_profile(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        Sanctum::actingAs($retailer->company->user, ['access']);

        // Action
        $response = $this->getJson('/v1/products/categories');

        // Assert
        $response->assertForbidden();
    }
}
