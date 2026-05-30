<?php

namespace App\Http\Controllers;

use App\Models\AccelerometerData;
use App\Models\GPSData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    /**
     * Display the historical logs page.
     */
    public function index(Request $request): View
    {
        // Get the requested date, default to today
        $dateString = $request->query('date');
        try {
            $selectedDate = $dateString ? Carbon::parse($dateString) : now()->timezone($this->dashboardTimezone());
        } catch (\Exception $e) {
            $selectedDate = now()->timezone($this->dashboardTimezone());
        }

        // Format to Y-m-d for querying
        $queryDate = $selectedDate->format('Y-m-d');

        // Fetch paginated accelerometer logs for the selected date (only seismic events)
        $accelerometerLogs = AccelerometerData::query()
            ->whereDate('recorded_at', $queryDate)
            ->where('magnitude', '>=', 0.15)
            ->orderByDesc('recorded_at')
            ->paginate(50, ['*'], 'accel_page')
            ->withQueryString();

        // Fetch paginated GPS logs for the selected date
        $gpsLogs = GPSData::query()
            ->whereDate('recorded_at', $queryDate)
            ->orderByDesc('recorded_at')
            ->paginate(50, ['*'], 'gps_page')
            ->withQueryString();

        // Build JSON-ready arrays for the PDF generator (current page only)
        $tz = $this->dashboardTimezone();

        $accelDataForPdf = $accelerometerLogs->map(fn (AccelerometerData $s) => [
            'time' => Carbon::parse($s->recorded_at)->timezone($tz)->format('H:i:s'),
            'datetime' => Carbon::parse($s->recorded_at)->timezone($tz)->format('d M Y H:i:s'),
            'x' => round((float) $s->x, 2),
            'y' => round((float) $s->y, 2),
            'z' => round((float) $s->z, 2),
            'magnitude' => round((float) $s->magnitude, 4),
        ])->values()->all();

        $gpsDataForPdf = $gpsLogs->map(fn (GPSData $g) => [
            'time' => Carbon::parse($g->recorded_at)->timezone($tz)->format('H:i:s'),
            'datetime' => Carbon::parse($g->recorded_at)->timezone($tz)->format('d M Y H:i:s'),
            'latitude' => round((float) $g->latitude, 7),
            'longitude' => round((float) $g->longitude, 7),
            'altitude' => $g->altitude !== null ? round((float) $g->altitude, 2) : 0.0,
            'satellites' => (int) $g->satellites,
            'status' => $g->status ?? 'NO FIX',
        ])->values()->all();

        return view('history', [
            'selectedDate' => $queryDate,
            'accelerometerLogs' => $accelerometerLogs,
            'gpsLogs' => $gpsLogs,
            'accelDataForPdf' => $accelDataForPdf,
            'gpsDataForPdf' => $gpsDataForPdf,
        ]);
    }
}
