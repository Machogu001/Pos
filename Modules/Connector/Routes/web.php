<?php

use Illuminate\Support\Facades\Route;
use Modules\Connector\Http\Controllers\ApiTokenController;
use Modules\Connector\Http\Controllers\InstallController;

Route::middleware('web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu')->prefix('connector')->group(function () {
    Route::get('install', [InstallController::class, 'index']);
    Route::post('install', [InstallController::class, 'install']);
    Route::get('install/uninstall', [InstallController::class, 'uninstall']);
    Route::get('install/update', [InstallController::class, 'update']);

    // API Tokens
    Route::get('tokens', [ApiTokenController::class, 'index']);
    Route::post('tokens', [ApiTokenController::class, 'store']);
    Route::post('tokens/{id}/toggle', [ApiTokenController::class, 'toggle']);
    Route::delete('tokens/{id}', [ApiTokenController::class, 'destroy']);
});
