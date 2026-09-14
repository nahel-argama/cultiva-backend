<?php

use Cultiva\Models\Purchase\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::middleware('profile:retailer')
    ->group(function (): void {
        Route::post('offers/{offer}/purchase', [PurchaseController::class, 'store'])
            ->whereNumber('offer')
            ->name('purchases.store');
        Route::get('purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    });

Route::group([
    'prefix' => 'sales',
    'as' => 'sales.',
    'middleware' => 'profile:producer',
], function (): void {
    Route::get('', [PurchaseController::class, 'sales'])->name('index');
});
