<?php

use App\Http\Controllers\AlarmController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login.post');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/accelerometer', [\App\Http\Controllers\AccelerometerController::class, 'index'])->name('accelerometer');
    Route::get('/gps', [\App\Http\Controllers\GpsController::class, 'index'])->name('gps');
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    Route::get('/panel/data/realtime', [PanelController::class, 'realtimeData'])->name('panel.data.realtime');
    Route::get('/panel/data/log', [PanelController::class, 'realtimeLog'])->name('panel.data.log');
    Route::get('/history', [\App\Http\Controllers\HistoryController::class, 'index'])->name('history');
    Route::get('/controller', [\App\Http\Controllers\SensorControllerController::class, 'index'])->name('controller');

    // Sensor Command Routes
    Route::prefix('sensor-commands')->name('sensor-commands.')->group(function () {
        Route::post('/power', [\App\Http\Controllers\SensorCommandController::class, 'power'])->name('power');
        Route::post('/sensitivity', [\App\Http\Controllers\SensorCommandController::class, 'sensitivity'])->name('sensitivity');
        Route::post('/reset', [\App\Http\Controllers\SensorCommandController::class, 'reset'])->name('reset');
        Route::get('/state', [\App\Http\Controllers\SensorCommandController::class, 'state'])->name('state');
    });

    // User Management Routes (Admin Only)
    Route::middleware('is_admin')->group(function () {
        Route::get('/users', [\App\Http\Controllers\UserController::class, 'index'])->name('users.index');
        Route::post('/users', [\App\Http\Controllers\UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/role', [\App\Http\Controllers\UserController::class, 'updateRole'])->name('users.updateRole');
        Route::delete('/users/{user}', [\App\Http\Controllers\UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
