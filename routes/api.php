<?php

use Cultiva\Integrations\Geo\Controllers\GeoController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1',
], function (): void {
    Route::group([
        'namespace' => 'Auth',
        'prefix'    => 'auth',
        'as'        => 'auth.',
    ], function (): void {});

    Route::group([
        'namespace' => 'Consult',
        'prefix'    => 'consult',
        'as'        => 'consult.',
    ], function (): void {
        Route::get('cep/{cep}', [GeoController::class, 'search'])->name('search-cep')
            ->middleware('throttle:5,1')
            ->where('cep', '[0-9]{8}');
    });
});
