<?php

use App\Http\Controllers\AccelerometerController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GpsController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SensorCommandController;
use App\Http\Controllers\SensorControllerController;
use App\Http\Controllers\UserController;
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
    Route::get('/accelerometer', [AccelerometerController::class, 'index'])->name('accelerometer');
    Route::get('/gps', [GpsController::class, 'index'])->name('gps');
    Route::get('/panel', [PanelController::class, 'index'])->name('panel');
    Route::get('/panel/data/realtime', [PanelController::class, 'realtimeData'])->name('panel.data.realtime');
    Route::get('/panel/data/log', [PanelController::class, 'realtimeLog'])->name('panel.data.log');
    Route::get('/history', [HistoryController::class, 'index'])->name('history');
    Route::get('/controller', [SensorControllerController::class, 'index'])->name('controller');

    // Sensor Command Routes
    Route::prefix('sensor-commands')->name('sensor-commands.')->group(function () {
        Route::post('/power', [SensorCommandController::class, 'power'])->name('power');
        Route::post('/sensitivity', [SensorCommandController::class, 'sensitivity'])->name('sensitivity');
        Route::post('/reset', [SensorCommandController::class, 'reset'])->name('reset');
        Route::post('/reset-esp32', [SensorCommandController::class, 'resetEsp32'])->name('reset-esp32');
        Route::get('/state', [SensorCommandController::class, 'state'])->name('state');
    });

    // User Management Routes (Admin Only)
    Route::middleware('is_admin')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.updateRole');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
