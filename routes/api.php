<?php

use App\Http\Controllers\Api\DashboardDataController;
use App\Http\Controllers\Api\SensorDataController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', [DashboardDataController::class, 'show'])->name('dashboard.data');

Route::any('/sensors/gps', [SensorDataController::class, 'storeGps'])->name('api.sensors.gps.store');
Route::any('/sensors/accelerometer', [SensorDataController::class, 'storeAccelerometer'])->name('api.sensors.accelerometer.store');
Route::get('/sensors/latest', [SensorDataController::class, 'latest'])->name('api.sensors.latest');
Route::get('/sensors', [SensorDataController::class, 'index'])->name('api.sensors.index');

// ESP32 Command Polling Routes
Route::get('/sensor-commands/pending', [\App\Http\Controllers\Api\SensorCommandApiController::class, 'pending'])->name('api.sensor-commands.pending');
Route::patch('/sensor-commands/{id}/executed', [\App\Http\Controllers\Api\SensorCommandApiController::class, 'markExecuted'])->name('api.sensor-commands.executed');