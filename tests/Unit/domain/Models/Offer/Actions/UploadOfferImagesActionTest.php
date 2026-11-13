<?php

namespace Tests\Unit\domain\Models\Offer\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Offer\Actions\GetOfferImagesAction;
use Cultiva\Models\Offer\Actions\UploadOfferImagesAction;
use Cultiva\Models\Offer\DTO\OfferImageDTO;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadOfferImagesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_upload_images_and_assign_sequential_order(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);

        $files = [
            UploadedFile::fake()->image('photo1.jpg'),
            UploadedFile::fake()->image('photo2.png'),
        ];

        $sut = new UploadOfferImagesAction(new GetOfferImagesAction());

        // Action
        $result = $sut->execute($offer, $files);

        // Assert
        $this->assertCount(2, $result);
        $this->assertContainsOnlyInstancesOf(OfferImageDTO::class, $result);
        $this->assertSame(1, $result[0]->sequence);
        $this->assertSame(2, $result[1]->sequence);

        Storage::disk('public')->assertExists($result[0]->path);
        Storage::disk('public')->assertExists($result[1]->path);
    }

    public function test_should_fill_available_order_slots_when_existing_images_present(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);

        // Existing image at sequence 1
        Storage::disk('public')->put("product_images/{$offer->id}/1_existing.jpg", 'content');

        $file = UploadedFile::fake()->image('photo2.jpg');
        $sut = new UploadOfferImagesAction(new GetOfferImagesAction());

        // Action
        $result = $sut->execute($offer, [$file]);

        // Assert
        $this->assertCount(2, $result);
        $this->assertSame(1, $result[0]->sequence);
        $this->assertSame('existing', $result[0]->id);
        $this->assertSame(2, $result[1]->sequence);
    }

    public function test_should_throw_exception_when_total_images_exceed_maximum_of_three(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);

        // 2 existing images
        Storage::disk('public')->put("product_images/{$offer->id}/1_img1.jpg", 'content');
        Storage::disk('public')->put("product_images/{$offer->id}/2_img2.jpg", 'content');

        // Trying to upload 2 more (2 + 2 = 4 > 3)
        $files = [
            UploadedFile::fake()->image('photo3.jpg'),
            UploadedFile::fake()->image('photo4.jpg'),
        ];

        $sut = new UploadOfferImagesAction(new GetOfferImagesAction());

        // Action & Assert
        try {
            $sut->execute($offer, $files);
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
    }
}
