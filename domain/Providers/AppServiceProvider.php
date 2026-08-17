<?php

namespace Cultiva\Providers;

use Carbon\CarbonImmutable;
use Date;
use Illuminate\Support\ServiceProvider;

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
        //
    }

    private function configureDate(): void
    {
        Date::use(CarbonImmutable::class);
    }
}
