<?php

namespace Cultiva\Providers;

use Carbon\CarbonImmutable;
use Cultiva\Base\Observer\Contracts\EventSubjectInterface;
use Cultiva\Base\Observer\LaravelEventSubject;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\ServiceProvider;

use Cultiva\Models\DeliveryOrder\Listeners\CreateDeliveryOrderListener;
use Cultiva\Models\Purchase\Events\PurchaseCreated;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->configureDate();
        $this->app->bind(EventSubjectInterface::class, LaravelEventSubject::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(EventSubjectInterface $eventSubject): void
    {
        $eventSubject->attach(
            PurchaseCreated::class,
            CreateDeliveryOrderListener::class,
        );
    }

    private function configureDate(): void
    {
        Date::use(CarbonImmutable::class);
    }
}
