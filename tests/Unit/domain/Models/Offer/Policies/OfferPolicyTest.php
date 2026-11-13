<?php

namespace Tests\Unit\domain\Models\Offer\Policies;

use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Offer\Policies\OfferPolicy;
use Database\Factories\OfferFactory;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_allow_producer_who_owns_offer_to_update_and_delete_image(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        $sut = new OfferPolicy();

        // Action & Assert
        $this->assertTrue($sut->update($producer->company->user, $offer));
        $this->assertTrue($sut->deleteImage($producer->company->user, $offer));
    }

    public function test_should_deny_another_producer_from_updating_or_deleting_image(): void
    {
        // Arrange
        $producerA = ProducerFactory::new()->create();
        $producerB = ProducerFactory::new()->create();
        $offerA = OfferFactory::new()->create(['producer_id' => $producerA->id]);
        $sut = new OfferPolicy();

        // Action & Assert
        $this->assertFalse($sut->update($producerB->company->user, $offerA));
        $this->assertFalse($sut->deleteImage($producerB->company->user, $offerA));
    }

    public function test_should_deny_user_without_producer_profile(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $offer = OfferFactory::new()->create(['producer_id' => $producer->id]);
        $retailer = RetailerFactory::new()->create();
        $sut = new OfferPolicy();

        // Action & Assert
        $this->assertFalse($sut->update($retailer->company->user, $offer));
        $this->assertFalse($sut->deleteImage($retailer->company->user, $offer));
    }
}
