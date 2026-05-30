<?php

namespace App\Http\Controllers;

use App\Models\AccelerometerData;
use App\Models\GPSData;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(): View
    {
        $latestAccel = AccelerometerData::query()->latest('recorded_at')->first();
        $latestGps = GPSData::query()->latest('recorded_at')->first();

        $espConnected = ($latestAccel?->recorded_at && $latestAccel->recorded_at->diffInSeconds(now()) < 10)
            || ($latestGps?->recorded_at && $latestGps->recorded_at->diffInSeconds(now()) < 10);

        return view('controller', [
            'espConnected' => $espConnected,
            'lastDataAt' => $latestAccel?->recorded_at?->timezone('Asia/Jakarta')?->format('d M Y H:i:s')
                ?? $latestGps?->recorded_at?->timezone('Asia/Jakarta')?->format('d M Y H:i:s')
                ?? '--',
        ]);
    }
}
