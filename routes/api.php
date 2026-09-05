<?php

use Cultiva\Auth\Controllers\LoginController;
use Cultiva\Auth\Controllers\RegisterController;
use Cultiva\Integrations\Geo\Controllers\GeoController;
use Cultiva\Models\Category\Http\Controllers\CategoryController;
use Cultiva\Models\Offer\Http\Controllers\OfferController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1',
], function (): void {
    Route::group([
        'prefix' => 'auth',
        'as' => 'auth.',
    ], function (): void {
        Route::post('signup', [RegisterController::class, 'signUp'])->name('signup');
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

            Route::middleware('profile:producer')
                ->name('producer.')
                ->group(function (): void {
                    Route::get('products/categories', [CategoryController::class, 'index'])->name('categories.index');
                    Route::post('offers', [OfferController::class, 'store'])->name('offers.store');
                    Route::get('offers/{offer}', [OfferController::class, 'show'])
                        ->whereNumber('offer')
                        ->name('offers.show');
                    Route::patch('offers/{offer}', [OfferController::class, 'update'])
                        ->whereNumber('offer')
                        ->name('offers.update');
                });
        });
});
