<?php
use App\Models\AccelerometerData;
use App\Models\GPSData;
use App\Models\SeismicEvent;
use Illuminate\Support\Carbon;

$now = Carbon::now();

for ($i = 20; $i >= 0; $i--) {
    $time = (clone $now)->subSeconds($i * 5);
    
    $isQuake = $i >= 2 && $i <= 8;
    
    if ($isQuake) {
        $x = rand(-800, 800) / 100;
        $y = rand(-800, 800) / 100;
        $z = rand(500, 1500) / 100;
        $magnitude = rand(400, 1500) / 100; // Between 4.0 and 15.0
    } else {
        $x = rand(-20, 20) / 100;
        $y = rand(-20, 20) / 100;
        $z = rand(900, 1050) / 100;
        $magnitude = rand(0, 30) / 100; // Below 0.34
    }

    AccelerometerData::create([
        'device_id' => 'ESP32_DUMMY',
        'x' => $x,
        'y' => $y,
        'z' => $z,
        'magnitude' => $magnitude,
        'recorded_at' => $time,
        'created_at' => $time,
        'updated_at' => $time,
    ]);
}

GPSData::create([
    'device_id' => 'ESP32_DUMMY',
    'latitude' => -7.797068,
    'longitude' => 110.370529,
    'altitude' => 114.0,
    'satellites' => 12,
    'status' => '3D FIX',
    'recorded_at' => $now,
    'created_at' => $now,
    'updated_at' => $now,
]);

$accel = AccelerometerData::where('magnitude', '>=', 4.0)->first();

SeismicEvent::create([
    'device_id' => 'ESP32_DUMMY',
    'accelerometer_data_id' => $accel ? $accel->id : 1,
    'latitude' => -7.797068,
    'longitude' => 110.370529,
    'altitude' => 114.0,
    'magnitude' => 8.5,
    'mmi_level' => 'V',
    'mmi_status' => 'Bahaya!',
    'recorded_at' => $now,
    'created_at' => $now,
    'updated_at' => $now,
]);

echo "Dummy data created successfully.\n";
