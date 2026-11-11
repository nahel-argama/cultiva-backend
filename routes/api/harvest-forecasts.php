<?php

use Cultiva\Models\HarvestForecast\Controllers\HarvestForecastController;
use Illuminate\Support\Facades\Route;

Route::middleware('profile:producer')
    ->get('harvest-season-periods', [HarvestForecastController::class, 'seasonPeriods'])
    ->name('harvest-season-periods.index');

Route::middleware('profile:producer,retailer')
    ->get('harvest-forecasts', [HarvestForecastController::class, 'index'])
    ->name('harvest-forecasts.index');

Route::middleware('profile:producer')
    ->name('harvest-forecasts.')
    ->group(function (): void {
        Route::post('harvest-forecasts', [HarvestForecastController::class, 'store'])->name('store');
        Route::patch('harvest-forecasts/{forecast}', [HarvestForecastController::class, 'update'])
            ->whereNumber('forecast')
            ->name('update');
        Route::delete('harvest-forecasts/{forecast}', [HarvestForecastController::class, 'destroy'])
            ->whereNumber('forecast')
            ->name('destroy');
    });
