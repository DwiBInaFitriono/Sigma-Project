<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class AccelerometerController extends Controller
{
    public function index(): View
    {
        return view('accelerometer', array_merge($this->dashboardPayload(10), [
            'dashboardDataUrl' => route('panel.data.realtime'),
            'logDataUrl' => route('panel.data.log'),
            'accelLog' => $this->collectAccelerometerLog(5),
        ]));
    }
}
