<?php

use Cultiva\Auth\Controllers\LoginController;
use Cultiva\Auth\Controllers\RegisterController;
use Cultiva\Integrations\Geo\Controllers\GeoController;
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
});
