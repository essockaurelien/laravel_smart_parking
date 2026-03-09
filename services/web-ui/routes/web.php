<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthUiController;
use App\Http\Controllers\MonitorController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/login', [AuthUiController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthUiController::class, 'login']);
Route::get('/register', [AuthUiController::class, 'showRegister']);
Route::post('/register', [AuthUiController::class, 'register']);
Route::post('/logout', [AuthUiController::class, 'logout']);

Route::get('/monitor', [MonitorController::class, 'show']);

Route::middleware('web.auth')->group(function () {
	Route::get('/', [DashboardController::class, 'index']);
	Route::post('/reservations', [DashboardController::class, 'reserve']);
	Route::post('/reservations/{reservationId}/cancel', [DashboardController::class, 'cancelReservation']);
	Route::post('/charge-quotes', [DashboardController::class, 'quoteCharge']);
	Route::post('/charge-requests', [DashboardController::class, 'charge']);
	Route::post('/checkin', [DashboardController::class, 'checkin']);
	Route::post('/checkout', [DashboardController::class, 'checkout']);
	Route::post('/pricing', [DashboardController::class, 'updatePricing']);
});
