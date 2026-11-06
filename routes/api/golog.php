<?php

use Cultiva\Integrations\GoLog\Http\Controllers\GoLogDriverController;
use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => 'golog',
    'as' => 'golog.',
], function (): void {
    Route::post('users', [GoLogDriverController::class, 'storeUser'])->name('users.store');

    Route::middleware(['auth:sanctum', 'ability:access'])
        ->group(function (): void {
            Route::post('drivers', [GoLogDriverController::class, 'storeDriver'])->name('drivers.store');
            Route::post('vehicles', [GoLogDriverController::class, 'storeVehicle'])->name('vehicles.store');
            Route::post('equipament-groups', [GoLogDriverController::class, 'storeEquipamentGroup'])->name('equipament-groups.store');
            Route::post('work-schedules', [GoLogDriverController::class, 'storeWorkSchedule'])->name('work-schedules.store');
        });
});
