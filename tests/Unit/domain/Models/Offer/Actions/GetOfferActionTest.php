<?php

namespace Tests\Unit\domain\Models\Offer\Actions;

use Cultiva\Models\Offer\Actions\GetAvailableOfferAction;
use Cultiva\Models\Offer\Actions\GetOfferAction;
use Cultiva\Models\Offer\Actions\GetProducerOfferAction;
use Cultiva\Models\Offer\Offer;
use Database\Factories\ProducerFactory;
use Database\Factories\RetailerFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class GetOfferActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_delegate_to_producer_action_when_user_is_producer(): void
    {
        // Arrange
        $producer = ProducerFactory::new()->create();
        $user = $producer->company->user;
        $offer = Mockery::mock(Offer::class);

        // Expects
        $producerActionMock = Mockery::mock(GetProducerOfferAction::class, function (MockInterface $mock) use ($producer, $offer) {
            $mock->shouldReceive('execute')
                ->once()
                ->with(Mockery::on(fn ($p) => $p->id === $producer->id), 123)
                ->andReturn($offer);
        });
        $availableActionMock = Mockery::mock(GetAvailableOfferAction::class);

        $sut = new GetOfferAction($producerActionMock, $availableActionMock);

        // Action
        $result = $sut->execute($user, 123);

        // Assert
        $this->assertSame($offer, $result);
    }

    public function test_should_delegate_to_available_action_when_user_is_retailer(): void
    {
        // Arrange
        $retailer = RetailerFactory::new()->create();
        $user = $retailer->company->user;
        $offer = Mockery::mock(Offer::class);

        // Expects
        $producerActionMock = Mockery::mock(GetProducerOfferAction::class);
        $availableActionMock = Mockery::mock(GetAvailableOfferAction::class, function (MockInterface $mock) use ($offer) {
            $mock->shouldReceive('execute')
                ->once()
                ->with(123)
                ->andReturn($offer);
        });

        $sut = new GetOfferAction($producerActionMock, $availableActionMock);

        // Action
        $result = $sut->execute($user, 123);

        // Assert
        $this->assertSame($offer, $result);
    }
}
