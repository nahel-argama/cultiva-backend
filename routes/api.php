<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1',
], function (): void {
    require __DIR__.'/api/auth.php';
    require __DIR__.'/api/consult.php';

    Route::middleware(['auth:sanctum', 'ability:access'])
        ->group(function (): void {
            require __DIR__.'/api/offers.php';
            require __DIR__.'/api/purchases.php';
            require __DIR__.'/api/wishlist.php';
        });
});
