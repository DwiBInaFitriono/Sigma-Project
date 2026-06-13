<?php

namespace App\Http\Controllers;

use App\Models\GPSData;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function index(): View
    {
        // GPS selalu dikirim ESP32 setiap 2 detik, jadi created_at GPS = heartbeat device
        $latestGps = GPSData::query()->latest('created_at')->first();
        $espConnected = $latestGps
            && $latestGps->created_at
            && $latestGps->created_at->diffInSeconds(now()) < 60;

        return view('controller', [
            'espConnected' => $espConnected,
            'lastDataAt' => $latestGps?->created_at?->timezone('Asia/Jakarta')?->format('d M Y H:i:s')
                ?? '--',
        ]);
    }
}
