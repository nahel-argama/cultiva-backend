<?php

namespace Tests\Unit\domain\Models\Offer\Actions;

use Cultiva\Models\Offer\Actions\GetOfferImagesAction;
use Cultiva\Models\Offer\DTO\OfferImageDTO;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GetOfferImagesActionTest extends TestCase
{
    public function test_should_return_empty_array_when_no_images_exist(): void
    {
        // Arrange
        Storage::fake('public');
        $sut = new GetOfferImagesAction();

        // Action
        $result = $sut->execute(999);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function test_should_return_sorted_offer_image_dtos(): void
    {
        // Arrange
        Storage::fake('public');
        $offerId = 42;

        // Create files out of order
        Storage::disk('public')->put("product_images/{$offerId}/3_third.jpg", 'image3');
        Storage::disk('public')->put("product_images/{$offerId}/1_first.png", 'image1');
        Storage::disk('public')->put("product_images/{$offerId}/2_second.webp", 'image2');

        $sut = new GetOfferImagesAction();

        // Action
        $result = $sut->execute($offerId);

        // Assert
        $this->assertCount(3, $result);
        $this->assertContainsOnlyInstancesOf(OfferImageDTO::class, $result);

        $this->assertSame('first', $result[0]->id);
        $this->assertSame(1, $result[0]->sequence);
        $this->assertSame(Storage::disk('public')->url("product_images/{$offerId}/1_first.png"), $result[0]->url);
        $this->assertSame("product_images/{$offerId}/1_first.png", $result[0]->path);

        $this->assertSame('second', $result[1]->id);
        $this->assertSame(2, $result[1]->sequence);

        $this->assertSame('third', $result[2]->id);
        $this->assertSame(3, $result[2]->sequence);
    }

    public function test_should_ignore_unrecognized_files(): void
    {
        // Arrange
        Storage::fake('public');
        $offerId = 15;

        Storage::disk('public')->put("product_images/{$offerId}/1_valid.jpg", 'image1');
        Storage::disk('public')->put("product_images/{$offerId}/random_file.txt", 'ignore');

        $sut = new GetOfferImagesAction();

        // Action
        $result = $sut->execute($offerId);

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame('valid', $result[0]->id);
    }
}
