<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HueController;
use App\Http\Controllers\Api\MqttController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/mqtt/publish', [MqttController::class, 'publish']);
Route::get('/mqtt/status', [MqttController::class, 'status']);
Route::get('/hue/lights', [HueController::class, 'lights']);
Route::post('/hue/lights/{lightId}', [HueController::class, 'setLight']);

Route::prefix('internal')->middleware('internal.token')->group(function () {
	Route::post('/spot/occupancy', [HueController::class, 'setSpotOccupancy']);
});
