<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SensorCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SensorCommandApiController extends Controller
{
    /**
     * ESP32 polling endpoint — returns all pending commands.
     */
    public function pending(Request $request): JsonResponse
    {
        $sensorType = $request->query('sensor_type');

        $query = SensorCommand::query()->pending()->latest();

        if ($sensorType) {
            $query->where('sensor_type', $sensorType);
        }

        $commands = $query->get()->map(fn (SensorCommand $command) => [
            'id' => $command->id,
            'sensor_type' => $command->sensor_type,
            'command' => $command->command,
            'payload' => $command->payload,
            'created_at' => $command->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'commands' => $commands,
            'count' => $commands->count(),
        ]);
    }

    /**
     * ESP32 marks a command as executed.
     */
    public function markExecuted(int $id): JsonResponse
    {
        $command = SensorCommand::findOrFail($id);
        $command->update(['status' => 'executed']);

        return response()->json([
            'success' => true,
            'message' => "Command #{$id} marked as executed.",
        ]);
    }
}
