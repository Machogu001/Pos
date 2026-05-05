<?php

use Illuminate\Support\Facades\Route;
use Modules\Manufacturing\Http\Controllers\ProductionController;
use Modules\Manufacturing\Http\Controllers\InstallController;

Route::middleware('web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu')->prefix('manufacturing')->group(function () {
    Route::get('install', [InstallController::class, 'index']);
    Route::post('install', [InstallController::class, 'install']);
    Route::get('install/uninstall', [InstallController::class, 'uninstall']);
    Route::get('install/update', [InstallController::class, 'update']);

    // Productions
    Route::get('productions', [ProductionController::class, 'index']);
    Route::post('productions', [ProductionController::class, 'store']);
    Route::get('productions/{id}', [ProductionController::class, 'show']);
    Route::put('productions/{id}', [ProductionController::class, 'update']);
    Route::delete('productions/{id}', [ProductionController::class, 'destroy']);
});
