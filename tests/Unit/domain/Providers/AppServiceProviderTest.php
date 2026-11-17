<?php

namespace Tests\Unit\domain\Providers;

use Cultiva\Base\Observer\Contracts\EventSubjectInterface;
use Cultiva\Models\DeliveryOrder\Listeners\CreateDeliveryOrderListener;
use Cultiva\Models\Purchase\Events\PurchaseCreated;
use Cultiva\Providers\AppServiceProvider;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class AppServiceProviderTest extends TestCase
{
    public function test_should_attach_purchase_created_observer_using_event_subject_interface(): void
    {
        // Arrange
        $sut = new AppServiceProvider($this->app);

        // Expects
        $eventSubjectMock = Mockery::mock(EventSubjectInterface::class, function (MockInterface $mock): void {
            $mock->shouldReceive('attach')
                ->once()
                ->with(PurchaseCreated::class, CreateDeliveryOrderListener::class);
        });

        // Action
        $sut->boot($eventSubjectMock);

        // Assert
        $this->addToAssertionCount(1);
    }

    public function test_should_register_create_delivery_order_listener_update_method_for_purchase_created(): void
    {
        // Arrange
        Event::fake();

        // Action & Assert
        Event::assertListening(
            PurchaseCreated::class,
            [CreateDeliveryOrderListener::class, 'update'],
        );
    }
}
