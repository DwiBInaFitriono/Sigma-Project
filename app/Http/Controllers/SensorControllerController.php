<?php

namespace App\Http\Controllers;

use App\Models\AccelerometerData;
use App\Models\GPSData;
use App\Models\SensorCommand;
use Illuminate\View\View;

class SensorControllerController extends Controller
{
    public function index(): View
    {
        $latestAccel = AccelerometerData::query()->latest('recorded_at')->first();
        $latestGps = GPSData::query()->latest('recorded_at')->first();

        $accelConnected = $latestAccel?->recorded_at && $latestAccel->recorded_at->diffInSeconds(now()) < 10;
        $gpsConnected = $latestGps?->recorded_at && $latestGps->recorded_at->diffInSeconds(now()) < 10;

        return view('controller', [
            'accelConnected' => $accelConnected,
            'gpsConnected' => $gpsConnected,
            'accelPower' => SensorCommand::latestPowerState('accelerometer'),
            'gpsPower' => SensorCommand::latestPowerState('gps'),
            'sensitivity' => SensorCommand::latestSensitivity(),
            'latestAccelAt' => $latestAccel?->recorded_at?->timezone('Asia/Jakarta')?->format('d M Y H:i:s') ?? '--',
            'latestGpsAt' => $latestGps?->recorded_at?->timezone('Asia/Jakarta')?->format('d M Y H:i:s') ?? '--',
        ]);
    }
}
