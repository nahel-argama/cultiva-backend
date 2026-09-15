<?php

use Cultiva\Auth\Controllers\LoginController;
use Cultiva\Auth\Controllers\RegisterController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'auth',
    'namespace' => 'Auth',
    'as' => 'auth.',
], function (): void {
    Route::post('signup', [RegisterController::class, 'signUp'])->name('signup');
    Route::get('signup/metadata', [RegisterController::class, 'metadata'])->name('metadata');
    Route::post('login', [LoginController::class, 'login'])->name('login');
});
