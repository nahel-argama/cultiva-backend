<?php

use Cultiva\Auth\Controllers\LoginController;
use Cultiva\Auth\Controllers\RegisterController;
use Cultiva\Integrations\Geo\Controllers\GeoController;
use Cultiva\Models\Category\Http\Controllers\CategoryController;
use Cultiva\Models\Offer\Http\Controllers\OfferController;
use Cultiva\Models\Purchase\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1',
], function (): void {
    Route::group([
        'prefix' => 'auth',
        'as' => 'auth.',
    ], function (): void {
        Route::post('signup', [RegisterController::class, 'signUp'])->name('signup');
        Route::get('signup/metadata', [RegisterController::class, 'metadata'])->name('signup.metadata');
        Route::post('login', [LoginController::class, 'login'])->name('login');
    });

    Route::group([
        'namespace' => 'Consult',
        'prefix' => 'consult',
        'as' => 'consult.',
    ], function (): void {
        Route::get('cep/{cep}', [GeoController::class, 'search'])->name('search-cep')
            ->middleware('throttle:5,1')
            ->where('cep', '[0-9]{8}');
    });

    Route::middleware(['auth:sanctum', 'ability:access'])
        ->group(function (): void {
            Route::get('offers', [OfferController::class, 'index'])->name('offers.index');
            Route::get('offers/{offer}', [OfferController::class, 'show'])
                ->whereNumber('offer')
                ->name('offers.show');

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

            Route::middleware('profile:producer')
                ->name('producer.')
                ->group(function (): void {
                    Route::get('products/categories', [CategoryController::class, 'index'])->name('categories.index');
                    Route::post('offers', [OfferController::class, 'store'])->name('offers.store');
                    Route::patch('offers/{offer}', [OfferController::class, 'update'])
                        ->whereNumber('offer')
                        ->name('offers.update');
                });
        });
});
