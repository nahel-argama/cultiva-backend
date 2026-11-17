<?php

namespace Cultiva\Base\Observer\Contracts;

interface EventObserverInterface
{
    public function update(object $event): void;
}
