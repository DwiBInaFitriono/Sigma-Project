<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccelerometerRequest;
use App\Http\Requests\StoreGpsRequest;
use App\Models\AccelerometerData;
use App\Models\GPSData;
use App\Models\SeismicEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class SensorDataController extends Controller
{
    public function storeGps(StoreGpsRequest $request): JsonResponse
    {
        if ($request->isMethod('get')) {
            return response()->json([
                'success' => true,
                'message' => 'Send sensor data with POST, PUT, or PATCH to save GPS readings.',
                'data' => [
                    'latest' => $this->formatGpsData(GPSData::query()->latest('recorded_at')->first()),
                    'method_allowed' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                ],
            ]);
        }

        $validated = $request->validated();

        $gpsData = GPSData::create([
            'device_id' => $validated['device_id'] ?? null,
            'latitude' => (float) $validated['latitude'],
            'longitude' => (float) $validated['longitude'],
            'altitude' => isset($validated['altitude']) ? (float) $validated['altitude'] : null,
            'satellites' => (int) ($validated['satellites'] ?? 0),
            'status' => $validated['status'] ?? 'NO FIX',
            'recorded_at' => $this->resolveRecordedAt($validated['recorded_at'] ?? null),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'GPS data saved successfully.',
            'data' => $this->formatGpsData($gpsData),
        ], 201);
    }

    public function storeAccelerometer(StoreAccelerometerRequest $request): JsonResponse
    {
        if ($request->isMethod('get')) {
            return response()->json([
                'success' => true,
                'message' => 'Send sensor data with POST, PUT, or PATCH to save accelerometer readings.',
                'data' => [
                    'latest' => $this->formatAccelerometerData(AccelerometerData::query()->latest('recorded_at')->first()),
                    'method_allowed' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
                ],
            ]);
        }

        $validated = $request->validated();

        $magnitude = isset($validated['magnitude'])
            ? (float) $validated['magnitude']
            : round(sqrt(
                pow((float) $validated['x'], 2)
                + pow((float) $validated['y'], 2)
                + pow((float) $validated['z'], 2)
            ), 4);

        $accelerometerData = AccelerometerData::create([
            'device_id' => $validated['device_id'] ?? null,
            'x' => (float) $validated['x'],
            'y' => (float) $validated['y'],
            'z' => (float) $validated['z'],
            'magnitude' => $magnitude,
            'recorded_at' => $this->resolveRecordedAt($validated['recorded_at'] ?? null),
        ]);

        if ($magnitude >= 0.20) {
            $gpsData = GPSData::query()
                ->where('device_id', $accelerometerData->device_id)
                ->latest('recorded_at')
                ->first()
                ?? GPSData::query()->latest('recorded_at')->first();

            if ($gpsData) {
                $mmi = SeismicEvent::getMmiDetails($magnitude);
                SeismicEvent::create([
                    'device_id' => $accelerometerData->device_id,
                    'latitude' => $gpsData->latitude,
                    'longitude' => $gpsData->longitude,
                    'altitude' => $gpsData->altitude,
                    'magnitude' => $magnitude,
                    'mmi_level' => $mmi['level'],
                    'mmi_status' => $mmi['status'],
                    'accelerometer_data_id' => $accelerometerData->id,
                    'gps_data_id' => $gpsData->id,
                    'recorded_at' => $accelerometerData->recorded_at,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Accelerometer data saved successfully.',
            'data' => $this->formatAccelerometerData($accelerometerData),
        ], 201);
    }

    public function latest(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'gps' => $this->formatGpsData(GPSData::query()->latest('recorded_at')->first()),
                'accelerometer' => $this->formatAccelerometerData(AccelerometerData::query()->latest('recorded_at')->first()),
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'latest' => [
                    'gps' => $this->formatGpsData(GPSData::query()->latest('recorded_at')->first()),
                    'accelerometer' => $this->formatAccelerometerData(AccelerometerData::query()->latest('recorded_at')->first()),
                ],
                'recent_gps' => $this->collectGpsHistory(),
                'recent_accelerometer' => $this->collectAccelerometerHistory(),
            ],
        ]);
    }

    private function resolveRecordedAt(?string $recordedAt): Carbon
    {
        return $recordedAt ? Carbon::parse($recordedAt) : now();
    }

    private function toJakartaTimeString(?Carbon $recordedAt, string $format): ?string
    {
        if ($recordedAt === null) {
            return null;
        }

        return $recordedAt->timezone($this->dashboardTimezone())->format($format);
    }

    private function toJakartaTimeLabel(?Carbon $recordedAt, string $format): string
    {
        $formatted = $this->toJakartaTimeString($recordedAt, $format);

        if ($formatted === null) {
            return '--';
        }

        return $formatted.' WIB';
    }

    protected function formatGpsData(?GPSData $gpsData): array
    {
        if ($gpsData === null) {
            return [
                'device_id' => null,
                'latitude' => 0.0,
                'longitude' => 0.0,
                'altitude' => 0.0,
                'satellites' => 0,
                'status' => 'NO FIX',
                'recorded_at' => '--',
            ];
        }

        return [
            'device_id' => $gpsData->device_id,
            'latitude' => (float) $gpsData->latitude,
            'longitude' => (float) $gpsData->longitude,
            'altitude' => $gpsData->altitude === null ? null : (float) $gpsData->altitude,
            'satellites' => (int) $gpsData->satellites,
            'status' => $gpsData->status,
            'recorded_at' => $this->toJakartaTimeLabel($gpsData->recorded_at, 'd M Y H:i:s'),
        ];
    }

    protected function formatAccelerometerData(?AccelerometerData $accelerometerData): array
    {
        if ($accelerometerData === null) {
            return [
                'device_id' => null,
                'x' => 0.0,
                'y' => 0.0,
                'z' => 0.0,
                'magnitude' => 0.0,
                'recorded_at' => '--',
            ];
        }

        return [
            'device_id' => $accelerometerData->device_id,
            'x' => (float) $accelerometerData->x,
            'y' => (float) $accelerometerData->y,
            'z' => (float) $accelerometerData->z,
            'magnitude' => (float) $accelerometerData->magnitude,
            'recorded_at' => $this->toJakartaTimeLabel($accelerometerData->recorded_at, 'd M Y H:i:s'),
        ];
    }

    private function collectGpsHistory(): array
    {
        return GPSData::query()
            ->latest('recorded_at')
            ->limit(20)
            ->get()
            ->map(fn (GPSData $gpsData): array => $this->formatGpsData($gpsData))
            ->all();
    }

    private function collectAccelerometerHistory(): array
    {
        return AccelerometerData::query()
            ->latest('recorded_at')
            ->limit(20)
            ->get()
            ->map(fn (AccelerometerData $accelerometerData): array => $this->formatAccelerometerData($accelerometerData))
            ->all();
    }
}
