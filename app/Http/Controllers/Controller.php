<?php

namespace App\Http\Controllers;

use App\Models\AccelerometerData;
use App\Models\GPSData;
use App\Models\SeismicEvent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;

abstract class Controller
{
    protected function dashboardTimezone(): string
    {
        return 'Asia/Jakarta';
    }

    protected function formatWibTimestamp(?\DateTimeInterface $dateTime, string $format): string
    {
        if ($dateTime === null) {
            return '--';
        }

        return $dateTime->format($format).' WIB';
    }

    protected function dashboardPayload(int $sampleLimit = 12): array
    {
        $latestGps = GPSData::query()->latest('recorded_at')->first();
        $latestAccelerometer = AccelerometerData::query()->latest('recorded_at')->first();

        // Determine if the ESP32 device itself is online (accelerometer always uploads)
        $espConnected = $latestAccelerometer
            && $latestAccelerometer->recorded_at
            && $latestAccelerometer->recorded_at->diffInSeconds(now()) < 10;

        // Chart samples: all readings (including idle) for smooth graph
        $accelerometerSamples = AccelerometerData::query()
            ->latest('recorded_at')
            ->limit($sampleLimit)
            ->get();

        // Log samples: only entries with detected magnitude (>= 0.15 = MMI Level II-III)
        $accelerometerLogSamples = AccelerometerData::query()
            ->where('magnitude', '>=', 0.15)
            ->latest('recorded_at')
            ->limit($sampleLimit)
            ->get();

        $accelerometerSamples = $accelerometerSamples->sortBy('recorded_at')->values();
        $accelerometerLogSamples = $accelerometerLogSamples->sortBy('recorded_at')->values();

        $gpsLogSamples = GPSData::query()
            ->latest('recorded_at')
            ->limit($sampleLimit)
            ->get()
            ->sortBy('recorded_at')
            ->values();

        return [
            'gps' => $this->formatGpsData($latestGps, $espConnected),
            'currentAccel' => $this->formatAccelerometerData($latestAccelerometer),
            'accelSamples' => $this->formatAccelerometerSamples($accelerometerSamples),
            'accelLogSamples' => $this->formatAccelerometerSamplesWithMmi($accelerometerLogSamples),
            'gpsLogSamples' => $this->formatGpsSamples($gpsLogSamples),
            'summary' => $this->buildAccelerometerSummary($accelerometerSamples),
            'lastUpdatedAt' => $this->resolveLastUpdatedAt($latestGps, $latestAccelerometer),
            'seismicEvents' => $this->collectSeismicEvents(),
        ];
    }

    protected function collectSeismicEvents(): array
    {
        return SeismicEvent::query()
            ->where('recorded_at', '>=', Carbon::today())
            ->latest('recorded_at')
            ->get()
            ->map(function (SeismicEvent $event): array {
                $mmiColor = $this->getMmiStatus((float) $event->magnitude)['color'];

                return [
                    'id' => $event->id,
                    'device_id' => $event->device_id,
                    'latitude' => (float) $event->latitude,
                    'longitude' => (float) $event->longitude,
                    'altitude' => $event->altitude === null ? null : (float) $event->altitude,
                    'magnitude' => (float) $event->magnitude,
                    'mmi_level' => $event->mmi_level,
                    'mmi_status' => $event->mmi_status,
                    'mmi_color' => $mmiColor,
                    'recorded_at' => $this->formatWibTimestamp($event->recorded_at?->timezone($this->dashboardTimezone()), 'd M Y H:i:s'),
                ];
            })
            ->all();
    }

    /**
     * Format GPS data for the dashboard.
     *
     * @param  bool  $espConnected  Whether the ESP32 device is currently sending data (determined from accelerometer freshness).
     */
    protected function formatGpsData(?GPSData $gps, bool $espConnected = false): array
    {
        if ($gps === null) {
            return [
                'latitude' => 0.0,
                'longitude' => 0.0,
                'altitude' => 0.0,
                'satellites' => 0,
                'status' => 'NO FIX',
                'recorded_at' => '--',
                'is_connected' => $espConnected,
                'has_fix' => false,
            ];
        }

        $gpsDirectlyConnected = $gps->recorded_at && $gps->recorded_at->diffInSeconds(now()) < 10;
        $hasFix = (float) $gps->latitude !== 0.0 || (float) $gps->longitude !== 0.0;

        return [
            'latitude' => (float) $gps->latitude,
            'longitude' => (float) $gps->longitude,
            'altitude' => $gps->altitude === null ? 0.0 : (float) $gps->altitude,
            'satellites' => (int) $gps->satellites,
            'status' => $gps->status,
            'recorded_at' => $this->formatWibTimestamp($gps->recorded_at?->timezone($this->dashboardTimezone()), 'd M Y H:i:s'),
            'is_connected' => $espConnected || $gpsDirectlyConnected,
            'has_fix' => $hasFix,
        ];
    }

    protected function formatGpsSamples(EloquentCollection $samples): array
    {
        return $samples->map(function (GPSData $sample): array {
            return [
                'time' => $this->formatWibTimestamp($sample->recorded_at?->timezone($this->dashboardTimezone()), 'H:i:s'),
                'latitude' => (float) $sample->latitude,
                'longitude' => (float) $sample->longitude,
                'altitude' => (float) ($sample->altitude ?? 0.0),
                'satellites' => (int) $sample->satellites,
                'status' => $sample->status ?? 'NO FIX',
            ];
        })->all();
    }

    protected function formatAccelerometerData(?AccelerometerData $accelerometer): array
    {
        if ($accelerometer === null) {
            return [
                'x' => 0.0,
                'y' => 0.0,
                'z' => 0.0,
                'magnitude' => 0.0,
                'time' => '--',
                'sensor_time' => '--',
                'is_connected' => false,
            ];
        }

        return [
            'x' => (float) $accelerometer->x,
            'y' => (float) $accelerometer->y,
            'z' => (float) $accelerometer->z,
            'magnitude' => (float) $accelerometer->magnitude,
            'time' => $this->formatWibTimestamp($accelerometer->recorded_at?->timezone($this->dashboardTimezone()), 'd M Y H:i:s'),
            'sensor_time' => $this->formatWibTimestamp($accelerometer->recorded_at?->timezone($this->dashboardTimezone()), 'd M Y H:i:s'),
            'is_connected' => $accelerometer->recorded_at && $accelerometer->recorded_at->diffInSeconds(now()) < 10,
        ];
    }

    protected function formatAccelerometerSamples(EloquentCollection $samples): array
    {
        return $samples->map(function (AccelerometerData $sample): array {
            return [
                'time' => $this->formatWibTimestamp($sample->recorded_at?->timezone($this->dashboardTimezone()), 'H:i:s'),
                'x' => (float) $sample->x,
                'y' => (float) $sample->y,
                'z' => (float) $sample->z,
                'magnitude' => (float) $sample->magnitude,
            ];
        })->all();
    }

    /**
     * Format accelerometer samples including MMI level/status — used for log tables.
     */
    protected function formatAccelerometerSamplesWithMmi(EloquentCollection $samples): array
    {
        return $samples->map(function (AccelerometerData $sample): array {
            $mmi = $this->getMmiStatus((float) $sample->magnitude);

            return [
                'time' => $this->formatWibTimestamp($sample->recorded_at?->timezone($this->dashboardTimezone()), 'H:i:s'),
                'x' => (float) $sample->x,
                'y' => (float) $sample->y,
                'z' => (float) $sample->z,
                'magnitude' => (float) $sample->magnitude,
                'mmi_level' => $mmi['level'],
                'mmi_status' => $mmi['status'],
                'mmi_color' => $mmi['color'],
            ];
        })->all();
    }

    protected function buildAccelerometerSummary(?EloquentCollection $samples = null): array
    {
        $today = Carbon::today($this->dashboardTimezone());
        $todayQuery = AccelerometerData::query()->where('recorded_at', '>=', $today);

        $count = $todayQuery->count();
        if ($count === 0) {
            if ($samples !== null && ! $samples->isEmpty()) {
                return [
                    'maximum' => round((float) $samples->max(fn (AccelerometerData $sample): float => (float) $sample->magnitude), 4),
                    'average' => round((float) $samples->avg(fn (AccelerometerData $sample): float => (float) $sample->magnitude), 4),
                    'count' => $samples->count(),
                ];
            }

            return [
                'maximum' => 0.0,
                'average' => 0.0,
                'count' => 0,
            ];
        }

        return [
            'maximum' => round((float) $todayQuery->max('magnitude'), 4),
            'average' => round((float) $todayQuery->avg('magnitude'), 4),
            'count' => $count,
        ];
    }

    protected function resolveLastUpdatedAt(?GPSData $gps, ?AccelerometerData $accelerometer): ?string
    {
        $timestamps = array_filter([
            $gps?->recorded_at,
            $accelerometer?->recorded_at,
        ]);

        if ($timestamps === []) {
            return null;
        }

        $latestTimestamp = collect($timestamps)
            ->sortByDesc(fn ($timestamp) => $timestamp->timestamp)
            ->first();

        return $this->formatWibTimestamp($latestTimestamp?->timezone($this->dashboardTimezone()), 'd M Y H:i:s');
    }

    /**
     * Determine MMI level and status label from magnitude (PGA) value.
     * Thresholds mirror the getStatusMMI() function in main.ino.
     *
     * @return array{level: string, status: string, color: string}
     */
    protected function getMmiStatus(float $magnitude): array
    {
        if ($magnitude < 0.15) {
            return ['level' => 'I', 'status' => 'Aman', 'color' => '#22c55e'];
        }

        if ($magnitude < 0.30) {
            return ['level' => 'II-III', 'status' => 'Lemah', 'color' => '#86efac'];
        }

        if ($magnitude < 0.60) {
            return ['level' => 'IV', 'status' => 'Waspada', 'color' => '#f59e0b'];
        }

        if ($magnitude < 1.00) {
            return ['level' => 'V', 'status' => 'Bahaya!', 'color' => '#f97316'];
        }

        return ['level' => 'VI+', 'status' => 'AWAS!', 'color' => '#ef4444'];
    }

    /**
     * Collect accelerometer log entries from the last N minutes.
     * Only includes entries with detected seismic magnitude (>= 0.15).
     * Each entry includes MMI level and status.
     */
    protected function collectAccelerometerLog(int $minutes = 5): array
    {
        return AccelerometerData::query()
            ->where('recorded_at', '>=', Carbon::now()->subMinutes($minutes))
            ->where('magnitude', '>=', 0.15)
            ->orderByDesc('recorded_at')
            ->get()
            ->map(function (AccelerometerData $sample): array {
                $mmi = $this->getMmiStatus((float) $sample->magnitude);

                return [
                    'time' => $this->formatWibTimestamp($sample->recorded_at?->timezone($this->dashboardTimezone()), 'H:i:s'),
                    'x' => (float) $sample->x,
                    'y' => (float) $sample->y,
                    'z' => (float) $sample->z,
                    'magnitude' => (float) $sample->magnitude,
                    'mmi_level' => $mmi['level'],
                    'mmi_status' => $mmi['status'],
                    'mmi_color' => $mmi['color'],
                ];
            })
            ->all();
    }

    /**
     * Collect GPS log entries from the last N minutes.
     */
    protected function collectGpsLog(int $minutes = 5): array
    {
        return GPSData::query()
            ->where('recorded_at', '>=', Carbon::now()->subMinutes($minutes))
            ->orderByDesc('recorded_at')
            ->get()
            ->map(function (GPSData $gps): array {
                return [
                    'time' => $this->formatWibTimestamp($gps->recorded_at?->timezone($this->dashboardTimezone()), 'H:i:s'),
                    'latitude' => (float) $gps->latitude,
                    'longitude' => (float) $gps->longitude,
                    'altitude' => $gps->altitude === null ? 0.0 : (float) $gps->altitude,
                    'satellites' => (int) $gps->satellites,
                    'status' => $gps->status ?? 'NO FIX',
                ];
            })
            ->all();
    }
}
