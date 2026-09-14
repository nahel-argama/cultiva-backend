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

Route::middleware('profile:producer')
    ->get('sales', [PurchaseController::class, 'sales'])
    ->name('sales.index');
