<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        return view('dashboard', array_merge($this->dashboardPayload(10), [
            'dashboardDataUrl' => route('panel.data.realtime'),
        ]));
    }
}
