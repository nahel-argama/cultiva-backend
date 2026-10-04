<?php

namespace Cultiva\Models\DeliveryOrder\Listeners;

use Cultiva\Models\DeliveryOrder\Actions\CreateDeliveryOrderAction;
use Cultiva\Models\Purchase\Events\PurchaseCreated;

class CreateDeliveryOrderListener
{
    public function __construct(
        private CreateDeliveryOrderAction $action,
    ) {}

    public function handle(PurchaseCreated $event): void
    {
        $this->action->execute($event->purchase);
    }
}
