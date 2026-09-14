<?php

use Cultiva\Models\Wishlist\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('wishlist/analytics', [WishlistController::class, 'analytics'])
    ->name('wishlist.analytics');

Route::middleware('profile:retailer')
    ->group(function (): void {
        Route::post('wishlist/items', [WishlistController::class, 'store'])
            ->name('wishlist.items.store');
        Route::get('wishlist/items', [WishlistController::class, 'index'])
            ->name('wishlist.items.index');
        Route::delete('wishlist/items/{wishlistItem}', [WishlistController::class, 'destroy'])
            ->whereNumber('wishlistItem')
            ->name('wishlist.items.destroy');
    });
