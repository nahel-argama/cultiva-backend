<?php

namespace Tests\Feature\domain\Models\Offer\Controllers\OfferController;

use Cultiva\Models\Category\Category;
use Cultiva\Models\Category\Enums\Category as CategoryEnum;
use Database\Factories\ProducerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StoreWithImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_create_offer_with_valid_images_and_return_them_in_resource(): void
    {
        // Arrange
        Storage::fake('public');
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_success.php');
        Http::fake([
            'http://product-source.test/api/products/0002' => Http::response($fixture, 200),
        ]);
        $producer = ProducerFactory::new()->create();
        $category = Category::query()->where('name', CategoryEnum::LEGUMES)->firstOrFail();
        Sanctum::actingAs($producer->company->user, ['access']);

        $harvestDate = now()->addDays(5)->format('Y-m-d');
        $payload = [
            'source_product_id' => '0002',
            'category_id' => $category->id,
            'unit_price' => '12.50',
            'total_quantity' => 20,
            'harvest_date' => $harvestDate,
            'lifespan_days' => 7,
            'images' => [
                UploadedFile::fake()->image('first.jpg'),
                UploadedFile::fake()->image('second.png'),
            ],
        ];

        // Action
        $response = $this->post('/v1/offers', $payload);

        // Assert
        $response->assertCreated()
            ->assertJsonPath('data.source_product_id', '0002')
            ->assertJsonCount(2, 'data.images')
            ->assertJsonPath('data.images.0.sequence', 1)
            ->assertJsonPath('data.images.1.sequence', 2);

        $offerId = $response->json('data.id');
        $files = Storage::disk('public')->files("product_images/{$offerId}");
        $this->assertCount(2, $files);
    }

    public function test_should_reject_creation_when_more_than_three_images_provided(): void
    {
        // Arrange
        Storage::fake('public');
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $producer = ProducerFactory::new()->create();
        $category = Category::query()->where('name', CategoryEnum::LEGUMES)->firstOrFail();
        Sanctum::actingAs($producer->company->user, ['access']);

        $payload = [
            'source_product_id' => '0002',
            'category_id' => $category->id,
            'unit_price' => '12.50',
            'total_quantity' => 20,
            'harvest_date' => now()->addDays(5)->format('Y-m-d'),
            'lifespan_days' => 7,
            'images' => [
                UploadedFile::fake()->image('img1.jpg'),
                UploadedFile::fake()->image('img2.jpg'),
                UploadedFile::fake()->image('img3.jpg'),
                UploadedFile::fake()->image('img4.jpg'),
            ],
        ];

        // Action
        $response = $this->postJson('/v1/offers', $payload);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['images']);
    }

    public function test_should_reject_creation_when_invalid_image_file_provided(): void
    {
        // Arrange
        Storage::fake('public');
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $producer = ProducerFactory::new()->create();
        $category = Category::query()->where('name', CategoryEnum::LEGUMES)->firstOrFail();
        Sanctum::actingAs($producer->company->user, ['access']);

        $payload = [
            'source_product_id' => '0002',
            'category_id' => $category->id,
            'unit_price' => '12.50',
            'total_quantity' => 20,
            'harvest_date' => now()->addDays(5)->format('Y-m-d'),
            'lifespan_days' => 7,
            'images' => [
                UploadedFile::fake()->create('document.pdf', 100),
            ],
        ];

        // Action
        $response = $this->postJson('/v1/offers', $payload);

        // Assert
        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['images.0']);
    }
}
