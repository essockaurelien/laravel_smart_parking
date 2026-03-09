<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChargeRequestController;

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

Route::get('/charge-requests', [ChargeRequestController::class, 'index']);
Route::post('/charge-requests/quote', [ChargeRequestController::class, 'quote']);
Route::post('/charge-requests', [ChargeRequestController::class, 'store']);
Route::get('/charge-requests/{chargeRequest}', [ChargeRequestController::class, 'show']);
Route::post('/charge-requests/{chargeRequest}/cancel', [ChargeRequestController::class, 'cancel']);
Route::post('/charge-requests/{chargeRequest}/progress', [ChargeRequestController::class, 'progress']);
