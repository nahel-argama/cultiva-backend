<?php

namespace Tests\Unit\domain\Base\Observer;

use Cultiva\Base\Observer\Contracts\EventObserverInterface;
use Cultiva\Base\Observer\Contracts\EventSubjectInterface;
use Cultiva\Base\Observer\LaravelEventSubject;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tests\TestCase;

class LaravelEventSubjectTest extends TestCase
{
    public function test_should_implement_event_subject_interface(): void
    {
        // Arrange
        $dispatcherMock = Mockery::mock(Dispatcher::class);

        // Action
        $sut = new LaravelEventSubject($dispatcherMock);

        // Assert
        $this->assertInstanceOf(EventSubjectInterface::class, $sut);
    }

    public function test_should_attach_observer_update_method_to_dispatcher(): void
    {
        // Arrange
        $eventClass = stdClass::class;
        $observer = new class implements EventObserverInterface
        {
            public function update(object $event): void {}
        };
        $observerClass = $observer::class;

        // Expects
        $dispatcherMock = Mockery::mock(Dispatcher::class, function (MockInterface $mock) use ($eventClass, $observerClass): void {
            $mock->shouldReceive('listen')
                ->once()
                ->with($eventClass, [$observerClass, 'update']);
        });

        $sut = new LaravelEventSubject($dispatcherMock);

        // Action
        $sut->attach($eventClass, $observerClass);

        // Assert
        $this->addToAssertionCount(1);
    }

    public function test_should_throw_exception_when_attaching_class_that_does_not_implement_event_observer_interface(): void
    {
        // Arrange
        $dispatcherMock = Mockery::mock(Dispatcher::class);
        $sut = new LaravelEventSubject($dispatcherMock);

        // Action & Assert
        $this->expectException(InvalidArgumentException::class);
        $sut->attach(stdClass::class, stdClass::class);
    }

    public function test_should_detach_all_observers_for_event_via_dispatcher(): void
    {
        // Arrange
        $eventClass = stdClass::class;

        // Expects
        $dispatcherMock = Mockery::mock(Dispatcher::class, function (MockInterface $mock) use ($eventClass): void {
            $mock->shouldReceive('forget')
                ->once()
                ->with($eventClass);
        });

        $sut = new LaravelEventSubject($dispatcherMock);

        // Action
        $sut->detachAll($eventClass);

        // Assert
        $this->addToAssertionCount(1);
    }

    public function test_should_notify_observers_by_dispatching_event_via_dispatcher(): void
    {
        // Arrange
        $event = new stdClass;

        // Expects
        $dispatcherMock = Mockery::mock(Dispatcher::class, function (MockInterface $mock) use ($event): void {
            $mock->shouldReceive('dispatch')
                ->once()
                ->with($event);
        });

        $sut = new LaravelEventSubject($dispatcherMock);

        // Action
        $sut->notify($event);

        // Assert
        $this->addToAssertionCount(1);
    }

    public function test_should_work_with_laravel_event_fake_when_resolved_from_container(): void
    {
        // Arrange
        Event::fake();
        $event = new stdClass;
        $sut = $this->app->make(EventSubjectInterface::class);

        // Action
        $sut->notify($event);

        // Assert
        Event::assertDispatched(stdClass::class, fn (stdClass $dispatched): bool => $dispatched === $event);
    }
}
