<?php

use Cultiva\Auth\Controllers\LoginController;
use Cultiva\Auth\Controllers\RegisterController;
use Cultiva\Integrations\Geo\Controllers\GeoController;
use Cultiva\Models\Category\Http\Controllers\CategoryController;
use Cultiva\Models\Offer\Controllers\OfferController;
use Cultiva\Models\Purchase\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1',
], function (): void {
    Route::group([
        'prefix' => 'auth',
        'namespace' => 'Auth',
        'as' => 'auth.',
    ], function (): void {
        Route::post('signup', [RegisterController::class, 'signUp'])->name('signup');
        Route::get('signup/metadata', [RegisterController::class, 'metadata'])->name('metadata');
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

    Route::group([
        'middleware' => ['auth:sanctum', 'ability:access'],
    ], function (): void {
        Route::group([
            'middleware' => 'profile:producer,retailer',
            'namespace' => 'Offer',
            'as' => 'offer.',
        ], function (): void {
            Route::get('offers', [OfferController::class, 'index'])->name('index');
            Route::get('offers/{offer}', [OfferController::class, 'show'])
                ->whereNumber('offer')
                ->name('show');
        });

        Route::group([
            'middleware' => 'profile:retailer',
            'namespace' => 'Purchase',
            'as' => 'purchase.',
        ], function (): void {
            Route::post('offers/{offer}/purchase', [PurchaseController::class, 'store'])
                ->whereNumber('offer')
                ->name('store');
            Route::get('purchases', [PurchaseController::class, 'index'])->name('index');
        });

        Route::group([
            'middleware' => 'profile:producer',
            'as' => 'producer.',
        ], function (): void {
            Route::get('sales', [PurchaseController::class, 'sales'])->name('sales.index');
            Route::get('products/categories', [CategoryController::class, 'index'])->name('categories.index');
            Route::post('offers', [OfferController::class, 'store'])->name('offers.store');
            Route::patch('offers/{offer}', [OfferController::class, 'update'])
                ->whereNumber('offer')
                ->name('offers.update');
        });
    });
});
