<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class GpsController extends Controller
{
    /**
     * Display the GPS monitoring page.
     */
    public function index(): View
    {
        return view('gps', array_merge($this->dashboardPayload(), [
            'logDataUrl' => route('panel.data.log'),
            'gpsLog' => $this->collectGpsLog(5),
        ]));
    }
}
