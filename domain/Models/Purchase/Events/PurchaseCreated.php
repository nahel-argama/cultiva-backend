<?php

namespace Cultiva\Models\Purchase\Events;

use Cultiva\Models\Purchase\Purchase;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class PurchaseCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Purchase $purchase,
    ) {}
}
