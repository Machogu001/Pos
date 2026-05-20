<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MpesaCallbackController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();

});
Route::post('/mpesa/callback', [MpesaCallbackController::class, 'handleCallback']);

// Update webhook — called by the central server when pushing a new release.
// Authenticated by HMAC-SHA256 signature (X-Update-Signature header), not by session.
Route::post('/update/trigger', [\Modules\Superadmin\Http\Controllers\UpdateController::class, 'triggerWebhook']);
