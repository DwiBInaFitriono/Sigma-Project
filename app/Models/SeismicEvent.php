<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeismicEvent extends Model
{
    use HasFactory;

    protected $table = 'seismic_events';

    protected $fillable = [
        'device_id',
        'latitude',
        'longitude',
        'altitude',
        'magnitude',
        'mmi_level',
        'mmi_status',
        'accelerometer_data_id',
        'gps_data_id',
        'recorded_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'altitude' => 'float',
        'magnitude' => 'float',
        'recorded_at' => 'datetime',
    ];

    public static function getMmiDetails(float $magnitude): array
    {
        if ($magnitude < 0.34) {
            return [
                'level' => 'I',
                'status' => 'Aman',
                'color' => '#22c55e',
            ];
        } elseif ($magnitude < 2.8) {
            return [
                'level' => 'II-III',
                'status' => 'Lemah',
                'color' => '#86efac',
            ];
        } elseif ($magnitude < 7.8) {
            return [
                'level' => 'IV',
                'status' => 'Waspada',
                'color' => '#f59e0b',
            ];
        } elseif ($magnitude < 18.4) {
            return [
                'level' => 'V',
                'status' => 'Bahaya!',
                'color' => '#f97316',
            ];
        } else {
            return [
                'level' => 'VI+',
                'status' => 'AWAS!',
                'color' => '#ef4444',
            ];
        }
    }

    public function accelerometerData(): BelongsTo
    {
        return $this->belongsTo(AccelerometerData::class, 'accelerometer_data_id');
    }

    public function gpsData(): BelongsTo
    {
        return $this->belongsTo(GPSData::class, 'gps_data_id');
    }
}
