<?php

namespace App\Http\Controllers;

use App\Models\AccelerometerData;
use App\Models\GPSData;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(): View
    {
        $deviceId = 'esp32-sigma-01';
        $lastSeenAccelStr = Cache::get("device_last_seen_accel:{$deviceId}");
        $lastSeenGpsStr = Cache::get("device_last_seen_gps:{$deviceId}");

        $accelConnected = false;
        if ($lastSeenAccelStr) {
            $accelConnected = Carbon::parse($lastSeenAccelStr)->diffInSeconds(now()) < 60;
        }

        $gpsConnected = false;
        if ($lastSeenGpsStr) {
            $gpsConnected = Carbon::parse($lastSeenGpsStr)->diffInSeconds(now()) < 60;
        }

        $latestAccel = AccelerometerData::query()->latest('created_at')->first();
        $latestGps = GPSData::query()->latest('created_at')->first();

        $espConnected = $accelConnected
            || $gpsConnected
            || ($latestAccel?->created_at && $latestAccel->created_at->diffInSeconds(now()) < 60)
            || ($latestGps?->created_at && $latestGps->created_at->diffInSeconds(now()) < 60);

        return view('controller', [
            'espConnected' => $espConnected,
            'lastDataAt' => $latestAccel?->created_at?->timezone('Asia/Jakarta')?->format('d M Y H:i:s')
                ?? $latestGps?->created_at?->timezone('Asia/Jakarta')?->format('d M Y H:i:s')
                ?? '--',
        ]);
    }
}
