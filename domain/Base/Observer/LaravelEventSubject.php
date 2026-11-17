<?php

namespace Cultiva\Base\Observer;

use Cultiva\Base\Observer\Contracts\EventObserverInterface;
use Cultiva\Base\Observer\Contracts\EventSubjectInterface;
use Illuminate\Contracts\Events\Dispatcher;
use InvalidArgumentException;

final class LaravelEventSubject implements EventSubjectInterface
{
    public function __construct(
        private readonly Dispatcher $dispatcher,
    ) {}

    public function attach(string $eventClass, string $observerClass): void
    {
        if (! is_a($observerClass, EventObserverInterface::class, true)) {
            throw new InvalidArgumentException(
                sprintf('%s must implement %s.', $observerClass, EventObserverInterface::class)
            );
        }

        $this->dispatcher->listen($eventClass, [$observerClass, 'update']);
    }

    public function detachAll(string $eventClass): void
    {
        $this->dispatcher->forget($eventClass);
    }

    public function notify(object $event): void
    {
        $this->dispatcher->dispatch($event);
    }
}
