<?php

namespace Tests\Unit\domain\Models\Offer\Actions;

use Cultiva\Base\Exceptions\CultivaException;
use Cultiva\Models\Offer\Actions\DeleteOfferImageAction;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DeleteOfferImageActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_delete_image_file_when_it_exists(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);

        $filePath = "product_images/{$offer->id}/1_targetid.jpg";
        Storage::disk('public')->put($filePath, 'image data');

        $sut = new DeleteOfferImageAction();

        // Action
        $sut->execute($offer, 'targetid');

        // Assert
        Storage::disk('public')->assertMissing($filePath);
    }

    public function test_should_throw_404_when_image_does_not_exist(): void
    {
        // Arrange
        Storage::fake('public');
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);

        $sut = new DeleteOfferImageAction();

        // Action & Assert
        try {
            $sut->execute($offer, 'nonexistent');
            $this->fail('Expected CultivaException was not thrown.');
        } catch (CultivaException $e) {
            $this->assertSame(404, $e->getStatusCode());
        }
    }
}
