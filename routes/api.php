<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardDataController;
use App\Http\Controllers\Api\SensorCommandApiController;
use App\Http\Controllers\Api\SensorDataController;
use App\Http\Controllers\SensorCommandController;
use Illuminate\Support\Facades\Route;

// Public Auth routes
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/register', [AuthController::class, 'register'])->name('api.register');

// Public Telemetry storage and device endpoints
Route::any('/sensors/gps', [SensorDataController::class, 'storeGps'])->name('api.sensors.gps.store');
Route::any('/sensors/accelerometer', [SensorDataController::class, 'storeAccelerometer'])->name('api.sensors.accelerometer.store');
Route::get('/sensors/latest', [SensorDataController::class, 'latest'])->name('api.sensors.latest');
Route::get('/sensors', [SensorDataController::class, 'index'])->name('api.sensors.index');

// ESP32 Command Polling Routes
Route::get('/sensor-commands/pending', [SensorCommandApiController::class, 'pending'])->name('api.sensor-commands.pending');
Route::patch('/sensor-commands/{id}/executed', [SensorCommandApiController::class, 'markExecuted'])->name('api.sensor-commands.executed');

// Guarded routes for authenticated operators (mobile app)
Route::middleware('auth.api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/profile', [AuthController::class, 'profile'])->name('api.profile');
    Route::get('/dashboard', [DashboardDataController::class, 'show'])->name('api.dashboard.data');

    // Operator control panel routes
    Route::prefix('sensor-commands')->name('api.sensor-commands.')->group(function () {
        Route::get('/state', [SensorCommandController::class, 'state'])->name('state');
        Route::post('/power', [SensorCommandController::class, 'power'])->name('power');
        Route::post('/sensitivity', [SensorCommandController::class, 'sensitivity'])->name('sensitivity');
        Route::post('/reset', [SensorCommandController::class, 'reset'])->name('reset');
        Route::post('/reset-esp32', [SensorCommandController::class, 'resetEsp32'])->name('reset-esp32');
    });
});
