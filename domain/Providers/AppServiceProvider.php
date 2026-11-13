<?php

namespace Cultiva\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

use Cultiva\Models\DeliveryOrder\Listeners\CreateDeliveryOrderListener;
use Cultiva\Models\Offer\Offer;
use Cultiva\Models\Offer\Policies\OfferPolicy;
use Cultiva\Models\Purchase\Events\PurchaseCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

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
        Gate::policy(Offer::class, OfferPolicy::class);

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
