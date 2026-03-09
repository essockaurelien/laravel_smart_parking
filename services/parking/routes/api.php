<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\SpotController;

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

Route::get('/occupancy', [SpotController::class, 'occupancy']);

Route::get('/spots', [SpotController::class, 'index']);
Route::post('/spots', [SpotController::class, 'store']);
Route::patch('/spots/{spot}', [SpotController::class, 'update']);

Route::get('/reservations', [ReservationController::class, 'index']);
Route::post('/reservations', [ReservationController::class, 'store']);
Route::delete('/reservations/{reservation}', [ReservationController::class, 'cancel']);

Route::get('/sessions', [SessionController::class, 'index']);
Route::post('/checkin', [SessionController::class, 'checkin']);
Route::post('/checkout', [SessionController::class, 'checkout']);
