<?php

use Cultiva\Models\Category\Http\Controllers\CategoryController;
use Cultiva\Models\Offer\Controllers\OfferController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'offers',
    'as' => 'offers.',
], function (): void {
    Route::get('', [OfferController::class, 'index'])->name('index');
    Route::get('{offer}', [OfferController::class, 'show'])
        ->whereNumber('offer')
        ->name('show');
});

Route::middleware('profile:producer')
    ->name('producer.')
    ->group(function (): void {
        Route::get('products/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('offers', [OfferController::class, 'store'])->name('offers.store');
        Route::patch('offers/{offer}', [OfferController::class, 'update'])
            ->whereNumber('offer')
            ->name('offers.update');
    });
