<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\PricingController;

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

Route::get('/payments', [PaymentController::class, 'index']);
Route::middleware('internal.token')->post('/payments', [PaymentController::class, 'store']);

Route::get('/pricing', [PricingController::class, 'show']);
Route::post('/pricing', [PricingController::class, 'store']);
