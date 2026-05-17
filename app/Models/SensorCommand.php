<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SensorCommand extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'sensor_type',
        'command',
        'payload',
        'status',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Scope to get pending commands.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<SensorCommand>  $query
     * @return \Illuminate\Database\Eloquent\Builder<SensorCommand>
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Get the latest power state for a given sensor type.
     */
    public static function latestPowerState(string $sensorType): string
    {
        $command = self::query()
            ->where('sensor_type', $sensorType)
            ->whereIn('command', ['power_on', 'power_off'])
            ->latest()
            ->first();

        if (! $command) {
            return 'on'; // default state
        }

        return $command->command === 'power_on' ? 'on' : 'off';
    }

    /**
     * Get the latest sensitivity settings for accelerometer.
     *
     * @return array{x: float, y: float, z: float}
     */
    public static function latestSensitivity(): array
    {
        $command = self::query()
            ->where('sensor_type', 'accelerometer')
            ->where('command', 'set_sensitivity')
            ->latest()
            ->first();

        if (! $command || empty($command->payload)) {
            return ['x' => 5.0, 'y' => 5.0, 'z' => 5.0];
        }

        return [
            'x' => (float) ($command->payload['x'] ?? 5.0),
            'y' => (float) ($command->payload['y'] ?? 5.0),
            'z' => (float) ($command->payload['z'] ?? 5.0),
        ];
    }
}
