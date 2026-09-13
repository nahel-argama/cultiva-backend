<?php

namespace Tests\Unit\domain\Models\Offer\Actions;

use Cultiva\Models\Offer\Actions\GetAvailableOfferAction;
use Cultiva\Models\Offer\Enums\OfferStatus;
use Cultiva\Models\Offer\Offer;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetAvailableOfferActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_return_available_active_offer(): void
    {
        // Arrange
        $offer = OfferFactory::new()->create([
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 2,
        ]);
        $sut = new GetAvailableOfferAction();

        // Action
        $result = $sut->execute($offer->id);

        // Assert
        $this->assertInstanceOf(Offer::class, $result);
        $this->assertSame($offer->id, $result->id);
    }

    public function test_should_throw_model_not_found_when_offer_is_inactive(): void
    {
        // Arrange
        $offer = OfferFactory::new()->create([
            'status' => OfferStatus::INACTIVE,
            'total_quantity' => 10,
            'reserved_quantity' => 0,
        ]);
        $sut = new GetAvailableOfferAction();

        // Action & Assert
        $this->expectException(ModelNotFoundException::class);
        $sut->execute($offer->id);
    }

    public function test_should_throw_model_not_found_when_offer_has_no_available_stock(): void
    {
        // Arrange
        $offer = OfferFactory::new()->create([
            'status' => OfferStatus::ACTIVE,
            'total_quantity' => 5,
            'reserved_quantity' => 5,
        ]);
        $sut = new GetAvailableOfferAction();

        // Action & Assert
        $this->expectException(ModelNotFoundException::class);
        $sut->execute($offer->id);
    }
}
