class AccelerometerSample {
  final double x;
  final double y;
  final double z;
  final double magnitude;
  final String time;

  AccelerometerSample({
    required this.x,
    required this.y,
    required this.z,
    required this.magnitude,
    required this.time,
  });

  factory AccelerometerSample.fromJson(Map<String, dynamic> json) {
    return AccelerometerSample(
      x: (json['x'] as num?)?.toDouble() ?? 0.0,
      y: (json['y'] as num?)?.toDouble() ?? 0.0,
      z: (json['z'] as num?)?.toDouble() ?? 0.0,
      magnitude: (json['magnitude'] as num?)?.toDouble() ?? 0.0,
      time: json['time'] ?? '--',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'x': x,
      'y': y,
      'z': z,
      'magnitude': magnitude,
      'time': time,
    };
  }
}

class GPSData {
  final double latitude;
  final double longitude;
  final double altitude;
  final int satellites;
  final String status;
  final String recordedAt;
  final bool isConnected;

  GPSData({
    required this.latitude,
    required this.longitude,
    required this.altitude,
    required this.satellites,
    required this.status,
    required this.recordedAt,
    required this.isConnected,
  });

  factory GPSData.fromJson(Map<String, dynamic> json) {
    return GPSData(
      latitude: (json['latitude'] as num?)?.toDouble() ?? 0.0,
      longitude: (json['longitude'] as num?)?.toDouble() ?? 0.0,
      altitude: (json['altitude'] as num?)?.toDouble() ?? 0.0,
      satellites: json['satellites'] as int? ?? 0,
      status: json['status'] ?? 'NO FIX',
      recordedAt: json['recorded_at'] ?? '--',
      isConnected: json['is_connected'] ?? false,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'latitude': latitude,
      'longitude': longitude,
      'altitude': altitude,
      'satellites': satellites,
      'status': status,
      'recorded_at': recordedAt,
      'is_connected': isConnected,
    };
  }
}

class TelemetrySummary {
  final double maximum;
  final double average;
  final int count;

  TelemetrySummary({
    required this.maximum,
    required this.average,
    required this.count,
  });

  factory TelemetrySummary.fromJson(Map<String, dynamic> json) {
    return TelemetrySummary(
      maximum: (json['maximum'] as num?)?.toDouble() ?? 0.0,
      average: (json['average'] as num?)?.toDouble() ?? 0.0,
      count: json['count'] as int? ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'maximum': maximum,
      'average': average,
      'count': count,
    };
  }
}

class DashboardData {
  final GPSData gps;
  final Map<String, dynamic> currentAccel; // raw values
  final List<AccelerometerSample> accelSamples;
  final List<Map<String, dynamic>> accelLogSamples; // contains MMI descriptions
  final List<Map<String, dynamic>> gpsLogSamples;
  final List<Map<String, dynamic>> seismicEvents;
  final TelemetrySummary summary;
  final String lastUpdatedAt;

  DashboardData({
    required this.gps,
    required this.currentAccel,
    required this.accelSamples,
    required this.accelLogSamples,
    required this.gpsLogSamples,
    required this.seismicEvents,
    required this.summary,
    required this.lastUpdatedAt,
  });

  factory DashboardData.fromJson(Map<String, dynamic> json) {
    var accelSamplesList = (json['accelSamples'] as List?)
            ?.map((e) => AccelerometerSample.fromJson(e))
            .toList() ??
        [];

    var accelLogSamplesList = (json['accelLogSamples'] as List?)
            ?.map((e) => e as Map<String, dynamic>)
            .toList() ??
        [];

    var gpsLogSamplesList = (json['gpsLogSamples'] as List?)
            ?.map((e) => e as Map<String, dynamic>)
            .toList() ??
        [];

    return DashboardData(
      gps: GPSData.fromJson(json['gps'] ?? {}),
      currentAccel: json['currentAccel'] ?? {},
      accelSamples: accelSamplesList,
      accelLogSamples: accelLogSamplesList,
      gpsLogSamples: gpsLogSamplesList,
      seismicEvents: (json['seismicEvents'] as List?)?.map((e) => e as Map<String, dynamic>).toList() ?? [],
      summary: TelemetrySummary.fromJson(json['summary'] ?? {}),
      lastUpdatedAt: json['lastUpdatedAt'] ?? '--',
    );
  }
}
