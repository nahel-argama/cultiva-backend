<?php

namespace Cultiva\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

use Cultiva\Models\DeliveryOrder\Listeners\CreateDeliveryOrderListener;
use Cultiva\Models\Purchase\Events\PurchaseCreated;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->configureDate();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            PurchaseCreated::class,
            CreateDeliveryOrderListener::class,
        );
    }

    private function configureDate(): void
    {
        Date::use(CarbonImmutable::class);
    }
}
