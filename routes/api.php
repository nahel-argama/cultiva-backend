<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'v1',
], function (): void {
    Route::group([
        'namespace' => 'Auth',
        'prefix'    => 'auth',
        'as'        => 'auth.',
    ], function (): void {
        //
    });
});
