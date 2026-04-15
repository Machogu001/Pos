<?php

use App\Http\Controllers\Install;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Installation Web Routes
|--------------------------------------------------------------------------
|
| Routes related to installation of the software
|
*/

// If installation is complete, lock installer URLs at route level and redirect.
$install_completed = false;
try {
	$install_completed = file_exists(base_path('.env'))
		&& Schema::hasTable('users')
		&& Schema::hasTable('business')
		&& Schema::hasTable('admin_settings');
} catch (\Throwable $e) {
	$install_completed = false;
}

if ($install_completed) {
	Route::redirect('/install', '/login', 302);
	Route::redirect('/install/', '/login', 302);
	Route::redirect('/install-start', '/login', 302);
	Route::any('/install/{any}', function () {
		return redirect()->route('login');
	})->where('any', '.*');

	return;
}

Route::get('/install-start', [Install\InstallController::class, 'index'])->name('install.index');
Route::get('/install', [Install\InstallController::class, 'index']);
Route::get('/install/', [Install\InstallController::class, 'index']);
Route::get('/install/check-server', [Install\InstallController::class, 'checkServer'])->name('install.checkServer');
Route::get('/install/details', [Install\InstallController::class, 'details'])->name('install.details');
Route::post('/install/post-details', [Install\InstallController::class, 'postDetails'])->name('install.postDetails');
Route::post('/install/install-alternate', [Install\InstallController::class, 'installAlternate'])->name('install.installAlternate');
Route::get('/install/success', [Install\InstallController::class, 'success'])->name('install.success');

Route::get('/install/update', [Install\InstallController::class, 'updateConfirmation'])->name('install.updateConfirmation');
Route::post('/install/update', [Install\InstallController::class, 'update'])->name('install.update');
