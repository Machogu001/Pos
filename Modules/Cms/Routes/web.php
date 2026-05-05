<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\CmsPagesController;
use Modules\Cms\Http\Controllers\InstallController;

Route::middleware('web', 'authh', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu')->prefix('cms')->group(function () {
    Route::get('install', [InstallController::class, 'index']);
    Route::post('install', [InstallController::class, 'install']);
    Route::get('install/uninstall', [InstallController::class, 'uninstall']);
    Route::get('install/update', [InstallController::class, 'update']);

    // Pages
    Route::get('pages', [CmsPagesController::class, 'index']);
    Route::post('pages', [CmsPagesController::class, 'store']);
    Route::get('pages/{id}', [CmsPagesController::class, 'show']);
    Route::put('pages/{id}', [CmsPagesController::class, 'update']);
    Route::delete('pages/{id}', [CmsPagesController::class, 'destroy']);
});
