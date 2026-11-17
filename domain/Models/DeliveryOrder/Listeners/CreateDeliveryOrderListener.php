<?php

namespace Cultiva\Models\DeliveryOrder\Listeners;

use Cultiva\Base\Observer\Contracts\EventObserverInterface;
use Cultiva\Models\DeliveryOrder\Actions\CreateDeliveryOrderAction;
use Cultiva\Models\Purchase\Events\PurchaseCreated;
use InvalidArgumentException;

class CreateDeliveryOrderListener implements EventObserverInterface
{
    public function __construct(
        private CreateDeliveryOrderAction $action,
    ) {}

    public function update(object $event): void
    {
        if (! $event instanceof PurchaseCreated) {
            throw new InvalidArgumentException(
                sprintf('%s expects %s, got %s.', self::class, PurchaseCreated::class, $event::class)
            );
        }

        $this->handle($event);
    }

    public function handle(PurchaseCreated $event): void
    {
        $this->action->execute($event->purchase);
    }
}
