<?php

use Cultiva\Models\Wishlist\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'wishlist',
    'as' => 'wishlist.',
], function (): void {
    Route::get('analytics', [WishlistController::class, 'analytics'])->name('analytics');

    Route::group([
        'prefix' => 'items',
        'as' => 'items.',
        'middleware' => 'profile:retailer',
    ], function (): void {
        Route::post('', [WishlistController::class, 'store'])->name('store');
        Route::get('', [WishlistController::class, 'index'])->name('index');
        Route::delete('{wishlistItem}', [WishlistController::class, 'destroy'])
            ->whereNumber('wishlistItem')
            ->name('destroy');
    });
});
