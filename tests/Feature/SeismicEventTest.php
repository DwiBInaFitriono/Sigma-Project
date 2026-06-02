<?php

use App\Models\AccelerometerData;
use App\Models\GPSData;
use App\Models\SeismicEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('ingesting accelerometer data with magnitude < 1.5 does not create a seismic event', function () {
    // 1. Send GPS data
    $this->postJson(route('api.sensors.gps.store'), [
        'device_id' => 'device-1',
        'latitude' => -6.2000000,
        'longitude' => 106.8166667,
        'altitude' => 10.5,
        'satellites' => 8,
        'status' => '3D FIX',
        'recorded_at' => now()->toIso8601String(),
    ])->assertStatus(201);

    // 2. Send low magnitude accelerometer data
    $this->postJson(route('api.sensors.accelerometer.store'), [
        'device_id' => 'device-1',
        'x' => 0.5,
        'y' => 0.5,
        'z' => 0.5,
        'magnitude' => 0.866, // < 1.5
        'recorded_at' => now()->toIso8601String(),
    ])->assertStatus(201);

    expect(SeismicEvent::count())->toBe(0);
});

test('ingesting accelerometer data with magnitude >= 1.5 creates a seismic event with latest GPS data', function () {
    // 1. Send GPS data
    $this->postJson(route('api.sensors.gps.store'), [
        'device_id' => 'device-1',
        'latitude' => -6.2000000,
        'longitude' => 106.8166667,
        'altitude' => 10.5,
        'satellites' => 8,
        'status' => '3D FIX',
        'recorded_at' => now()->toIso8601String(),
    ])->assertStatus(201);

    // 2. Send high magnitude accelerometer data
    $this->postJson(route('api.sensors.accelerometer.store'), [
        'device_id' => 'device-1',
        'x' => 1.5,
        'y' => 1.5,
        'z' => 1.5,
        'magnitude' => 2.598, // >= 1.5
        'recorded_at' => now()->toIso8601String(),
    ])->assertStatus(201);

    expect(SeismicEvent::count())->toBe(1);

    $event = SeismicEvent::first();
    expect($event->magnitude)->toBe(2.598);
    expect($event->device_id)->toBe('device-1');
    expect($event->latitude)->toBe(-6.2000000);
    expect($event->longitude)->toBe(106.8166667);
    expect($event->mmi_level)->toBe('II-III'); // 2.598 is < 2.8, so II-III
    expect($event->mmi_status)->toBe('Lemah');
});

test('realtime data payload contains seismicEvents', function () {
    $user = User::factory()->create();

    // Seed GPS and high magnitude accelerometer to trigger seismic event
    $gps = GPSData::create([
        'device_id' => 'device-1',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'altitude' => 10.0,
        'recorded_at' => now(),
    ]);

    $accel = AccelerometerData::create([
        'device_id' => 'device-1',
        'x' => 1.0,
        'y' => 1.0,
        'z' => 1.0,
        'magnitude' => 1.732,
        'recorded_at' => now(),
    ]);

    SeismicEvent::create([
        'device_id' => 'device-1',
        'latitude' => $gps->latitude,
        'longitude' => $gps->longitude,
        'altitude' => $gps->altitude,
        'magnitude' => $accel->magnitude,
        'mmi_level' => 'II-III',
        'mmi_status' => 'Lemah',
        'accelerometer_data_id' => $accel->id,
        'gps_data_id' => $gps->id,
        'recorded_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('panel.data.realtime'));
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'seismicEvents' => [
            '*' => [
                'id',
                'device_id',
                'latitude',
                'longitude',
                'altitude',
                'magnitude',
                'mmi_level',
                'mmi_status',
                'mmi_color',
                'recorded_at',
            ],
        ],
    ]);
});
