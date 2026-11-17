<?php

namespace Cultiva\Base\Observer\Contracts;

interface EventSubjectInterface
{
    /**
     * @param  class-string  $eventClass
     * @param  class-string<EventObserverInterface>  $observerClass
     */
    public function attach(string $eventClass, string $observerClass): void;

    /**
     * @param  class-string  $eventClass
     */
    public function detachAll(string $eventClass): void;

    public function notify(object $event): void;
}
