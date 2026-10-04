<?php

use Cultiva\Models\DeliveryTrip\Http\Controllers\DeliveryTripController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'delivery',
    'as' => 'delivery.',
    'middleware' => 'profile:delivery',
], function (): void {
    Route::get('trips', [DeliveryTripController::class, 'index'])->name('trips.index');
    Route::get('trips/current', [DeliveryTripController::class, 'current'])->name('trips.current');
    Route::post('trips/{trip}/assign', [DeliveryTripController::class, 'assign'])->name('trips.assign');
    Route::post('trips/{trip}/cancel', [DeliveryTripController::class, 'cancel'])->name('trips.cancel');
    Route::post('stops/{stop}/confirm', [DeliveryTripController::class, 'confirmStop'])->name('stops.confirm');
});
