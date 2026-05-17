<?php

namespace App\Http\Controllers;

use App\Models\SensorCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SensorCommandController extends Controller
{
    /**
     * Toggle the power state of a sensor.
     */
    public function power(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sensor_type' => ['required', 'in:accelerometer,gps'],
            'state' => ['required', 'in:on,off'],
        ]);

        $command = SensorCommand::create([
            'sensor_type' => $validated['sensor_type'],
            'command' => $validated['state'] === 'on' ? 'power_on' : 'power_off',
            'payload' => null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Sensor {$validated['sensor_type']} diperintahkan untuk " . ($validated['state'] === 'on' ? 'dihidupkan' : 'dimatikan') . '.',
            'command_id' => $command->id,
            'power_state' => $validated['state'],
        ]);
    }

    /**
     * Update the sensitivity settings for the accelerometer.
     */
    public function sensitivity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'x' => ['required', 'numeric', 'min:1', 'max:10'],
            'y' => ['required', 'numeric', 'min:1', 'max:10'],
            'z' => ['required', 'numeric', 'min:1', 'max:10'],
        ]);

        $command = SensorCommand::create([
            'sensor_type' => 'accelerometer',
            'command' => 'set_sensitivity',
            'payload' => [
                'x' => (float) $validated['x'],
                'y' => (float) $validated['y'],
                'z' => (float) $validated['z'],
            ],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan sensitivitas akselerometer telah disimpan.',
            'command_id' => $command->id,
            'sensitivity' => $command->payload,
        ]);
    }

    /**
     * Reset a sensor to its default settings.
     */
    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sensor_type' => ['required', 'in:accelerometer,gps'],
        ]);

        $command = SensorCommand::create([
            'sensor_type' => $validated['sensor_type'],
            'command' => 'reset_default',
            'payload' => null,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Sensor {$validated['sensor_type']} telah direset ke pengaturan default.",
            'command_id' => $command->id,
        ]);
    }

    /**
     * Get the current UI state (power + sensitivity) for both sensors.
     */
    public function state(): JsonResponse
    {
        return response()->json([
            'accelerometer' => [
                'power' => SensorCommand::latestPowerState('accelerometer'),
                'sensitivity' => SensorCommand::latestSensitivity(),
            ],
            'gps' => [
                'power' => SensorCommand::latestPowerState('gps'),
            ],
        ]);
    }
}
