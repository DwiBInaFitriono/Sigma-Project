<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function index(): View
    {
        return view('dashboard', array_merge($this->dashboardPayload(), [
            'dashboardDataUrl' => route('panel.data.realtime'),
        ]));
    }

    public function realtimeData(): JsonResponse
    {
        return response()
            ->json($this->dashboardPayload(10))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function realtimeLog(): JsonResponse
    {
        return response()
            ->json([
                'accelLog' => $this->collectAccelerometerLog(5),
                'gpsLog' => $this->collectGpsLog(5),
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}