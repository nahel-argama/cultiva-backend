<?php

namespace Tests\Feature\domain\Models\Offer\Commands;

use Database\Factories\CategoryFactory;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PopulateProductImagesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_populate_image_for_specific_source_id(): void
    {
        // Arrange
        Storage::fake('public');
        config(['services.product_source.base_url' => 'http://product-source.test/api']);
        $fixture = require base_path('tests/Fixtures/Integrations/ProductSource/product_success.php');

        $gd = imagecreatetruecolor(10, 10);
        ob_start();
        imagejpeg($gd);
        $jpegBytes = ob_get_clean();
        imagedestroy($gd);

        Http::fake([
            'http://product-source.test/api/products/0002' => Http::response($fixture, 200),
            'duckduckgo.com/?*' => Http::response('<html><script>vqd=123-456&</script></html>', 200),
            'duckduckgo.com/i.js*' => Http::response([
                'results' => [
                    ['image' => 'https://images.example.com/item.jpg'],
                ],
            ], 200),
            'images.example.com/item.jpg' => Http::response($jpegBytes, 200, ['Content-Type' => 'image/jpeg']),
        ]);

        // Action & Assert
        $this->artisan('products:populate-images --source-id=0002')
            ->expectsOutputToContain('Populated image for product: 0002')
            ->assertSuccessful();

        $this->assertTrue(Storage::disk('public')->exists('products/0002.jpg'));
    }

    public function test_should_populate_images_for_all_existing_offers_when_no_source_id_option(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $category = CategoryFactory::new()->create();
        OfferFactory::new()->create([
            'producer_id' => $producer->id,
            'category_id' => $category->id,
            'source_product_id' => '0003',
            'product_name' => 'Cenoura',
        ]);

        $gd = imagecreatetruecolor(10, 10);
        ob_start();
        imagejpeg($gd);
        $jpegBytes = ob_get_clean();
        imagedestroy($gd);

        Http::fake([
            'duckduckgo.com/?*' => Http::response('<html><script>vqd=123-456&</script></html>', 200),
            'duckduckgo.com/i.js*' => Http::response([
                'results' => [
                    ['image' => 'https://images.example.com/cenoura.jpg'],
                ],
            ], 200),
            'images.example.com/cenoura.jpg' => Http::response($jpegBytes, 200, ['Content-Type' => 'image/jpeg']),
        ]);

        // Action & Assert
        $this->artisan('products:populate-images')
            ->expectsOutputToContain('Populated image for product: 0003')
            ->assertSuccessful();

        $this->assertTrue(Storage::disk('public')->exists('products/0003.jpg'));
    }
}
