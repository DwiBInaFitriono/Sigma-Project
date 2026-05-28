import 'dart:async';
import 'dart:convert';
import 'dart:math';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import '../models/user.dart';
import '../models/telemetry.dart';

class ApiService {
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal() {
    _initMockData();
  }

  // Configurations
  String _serverUrl = "http://10.0.2.2:8000"; // Default emulator address
  bool _isMockMode = false; // Default to API connection mode
  String? _token;
  User? _currentUser;

  // Persisted state keys
  static const String keyServerUrl = "sigma_server_url";
  static const String keyMockMode = "sigma_mock_mode";
  static const String keyToken = "sigma_token";
  static const String keyUser = "sigma_user";

  // Getters
  String get serverUrl => _serverUrl;
  bool get isMockMode => _isMockMode;
  String? get token => _token;
  User? get currentUser => _currentUser;
  bool get isAuthenticated => _token != null || (_isMockMode && _currentUser != null);

  // Initialize service
  Future<void> init() async {
    final prefs = await SharedPreferences.getInstance();
    _serverUrl = prefs.getString(keyServerUrl) ?? "http://10.0.2.2:8000";
    _isMockMode = prefs.getBool(keyMockMode) ?? false;
    _token = prefs.getString(keyToken);
    
    final userJson = prefs.getString(keyUser);
    if (userJson != null) {
      try {
        _currentUser = User.fromJson(jsonDecode(userJson));
      } catch (e) {
        _currentUser = null;
      }
    }
  }

  // Set configurations
  Future<void> setServerUrl(String url) async {
    _serverUrl = url;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(keyServerUrl, url);
  }

  Future<void> setMockMode(bool value) async {
    _isMockMode = value;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(keyMockMode, value);
    if (value) {
      // If switching to mock mode, auto-create a mock user if none exists
      if (_currentUser == null) {
        _currentUser = User(
          id: 1,
          name: "Operator Demo",
          email: "operator@sigma.com",
          role: "admin",
          token: "mock-token-123456",
        );
      }
    }
  }

  // ----------------------------------------------------
  // Authentication APIs
  // ----------------------------------------------------

  Future<bool> login(String email, String password) async {
    if (_isMockMode) {
      await Future.delayed(const Duration(milliseconds: 800)); // Simulate delay
      if (email.contains('@') && password.length >= 6) {
        _currentUser = User(
          id: 1,
          name: email.split('@')[0].toUpperCase(),
          email: email,
          role: email.startsWith('admin') ? 'admin' : 'user',
          token: "mock-session-token-abcxyz",
        );
        _token = _currentUser!.token;
        await _saveUserSession();
        return true;
      }
      throw Exception("Format email atau password minimal 6 karakter salah.");
    }

    try {
      final response = await http.post(
        Uri.parse("$_serverUrl/api/login"),
        headers: {"Content-Type": "application/json", "Accept": "application/json"},
        body: jsonEncode({"email": email, "password": password}),
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        _token = data['token'];
        _currentUser = User.fromJson(data['user'], token: _token);
        await _saveUserSession();
        return true;
      } else {
        final data = jsonDecode(response.body);
        throw Exception(data['message'] ?? "Login Gagal. Mohon periksa kredensial Anda.");
      }
    } catch (e) {
      throw Exception("Koneksi server gagal: $e");
    }
  }

  Future<bool> register(String name, String email, String password, String passwordConfirmation) async {
    if (_isMockMode) {
      await Future.delayed(const Duration(milliseconds: 800));
      _currentUser = User(
        id: Random().nextInt(1000) + 10,
        name: name,
        email: email,
        role: "user",
        token: "mock-session-token-newuser",
      );
      _token = _currentUser!.token;
      await _saveUserSession();
      return true;
    }

    try {
      final response = await http.post(
        Uri.parse("$_serverUrl/api/register"),
        headers: {"Content-Type": "application/json", "Accept": "application/json"},
        body: jsonEncode({
          "name": name,
          "email": email,
          "password": password,
          "password_confirmation": passwordConfirmation,
        }),
      );

      if (response.statusCode == 201 || response.statusCode == 200) {
        final data = jsonDecode(response.body);
        _token = data['token'];
        _currentUser = User.fromJson(data['user'], token: _token);
        await _saveUserSession();
        return true;
      } else {
        final data = jsonDecode(response.body);
        throw Exception(data['message'] ?? "Registrasi gagal.");
      }
    } catch (e) {
      throw Exception("Koneksi server gagal: $e");
    }
  }

  Future<void> logout() async {
    if (!_isMockMode && _token != null) {
      try {
        await http.post(
          Uri.parse("$_serverUrl/api/logout"),
          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
            "Authorization": "Bearer $_token",
          },
        );
      } catch (_) {}
    }

    _token = null;
    _currentUser = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(keyToken);
    await prefs.remove(keyUser);
  }

  Future<void> _saveUserSession() async {
    final prefs = await SharedPreferences.getInstance();
    if (_token != null) {
      await prefs.setString(keyToken, _token!);
    }
    if (_currentUser != null) {
      await prefs.setString(keyUser, jsonEncode(_currentUser!.toJson()));
    }
  }

  // ----------------------------------------------------
  // Dashboard Telemetry Data
  // ----------------------------------------------------

  Future<DashboardData> getDashboardData() async {
    if (_isMockMode) {
      await Future.delayed(const Duration(milliseconds: 300));
      _generateNextMockSample();
      return DashboardData.fromJson(_getMockDashboardJson());
    }

    try {
      final response = await http.get(
        Uri.parse("$_serverUrl/api/dashboard"),
        headers: {
          "Accept": "application/json",
          if (_token != null) "Authorization": "Bearer $_token",
        },
      );

      if (response.statusCode == 200) {
        return DashboardData.fromJson(jsonDecode(response.body));
      } else {
        throw Exception("Gagal mengambil data dashboard: ${response.statusCode}");
      }
    } catch (e) {
      throw Exception("Koneksi server gagal: $e");
    }
  }

  // ----------------------------------------------------
  // Sensor Command Panel
  // ----------------------------------------------------

  Future<Map<String, dynamic>> getSensorCommandsState() async {
    if (_isMockMode) {
      return {
        "accelerometer": {
          "power": _mockAccelPower ? "on" : "off",
          "sensitivity": {
            "x": _mockAccelSensitivity['x'],
            "y": _mockAccelSensitivity['y'],
            "z": _mockAccelSensitivity['z'],
          }
        },
        "gps": {
          "power": _mockGpsPower ? "on" : "off",
        }
      };
    }

    try {
      final response = await http.get(
        Uri.parse("$_serverUrl/api/sensor-commands/state"),
        headers: {
          "Accept": "application/json",
          if (_token != null) "Authorization": "Bearer $_token",
        },
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        throw Exception("Gagal mengambil state sensor.");
      }
    } catch (e) {
      throw Exception("Koneksi server gagal: $e");
    }
  }

  Future<bool> sendPowerCommand(String sensorType, String state) async {
    if (_isMockMode) {
      if (sensorType == 'accelerometer') {
        _mockAccelPower = state == 'on';
      } else {
        _mockGpsPower = state == 'on';
      }
      return true;
    }

    try {
      final response = await http.post(
        Uri.parse("$_serverUrl/api/sensor-commands/power"),
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          if (_token != null) "Authorization": "Bearer $_token",
        },
        body: jsonEncode({"sensor_type": sensorType, "state": state}),
      );
      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  Future<bool> sendSensitivityCommand(double x, double y, double z) async {
    if (_isMockMode) {
      _mockAccelSensitivity = {'x': x, 'y': y, 'z': z};
      return true;
    }

    try {
      final response = await http.post(
        Uri.parse("$_serverUrl/api/sensor-commands/sensitivity"),
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          if (_token != null) "Authorization": "Bearer $_token",
        },
        body: jsonEncode({"x": x, "y": y, "z": z}),
      );
      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  Future<bool> sendResetCommand(String sensorType) async {
    if (_isMockMode) {
      if (sensorType == 'accelerometer') {
        _mockAccelSensitivity = {'x': 4.0, 'y': 4.0, 'z': 4.0};
        _mockAccelPower = true;
      } else {
        _mockGpsPower = true;
      }
      return true;
    }

    try {
      final response = await http.post(
        Uri.parse("$_serverUrl/api/sensor-commands/reset"),
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          if (_token != null) "Authorization": "Bearer $_token",
        },
        body: jsonEncode({"sensor_type": sensorType}),
      );
      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  Future<bool> sendEsp32Reset() async {
    if (_isMockMode) {
      await Future.delayed(const Duration(milliseconds: 500));
      _mockAccelSensitivity = {'x': 4.0, 'y': 4.0, 'z': 4.0};
      _mockAccelPower = true;
      _mockGpsPower = true;
      return true;
    }

    try {
      final response = await http.post(
        Uri.parse("$_serverUrl/api/sensor-commands/reset-esp32"),
        headers: {
          "Content-Type": "application/json",
          "Accept": "application/json",
          if (_token != null) "Authorization": "Bearer $_token",
        },
        body: jsonEncode({}),
      );
      return response.statusCode == 200;
    } catch (e) {
      return false;
    }
  }

  // ----------------------------------------------------
  // Offline Mock Data Engine (Local Simulator)
  // ----------------------------------------------------

  bool _mockAccelPower = true;
  bool _mockGpsPower = true;
  Map<String, double> _mockAccelSensitivity = {'x': 4.0, 'y': 4.0, 'z': 4.0};

  double _gpsLat = -6.2088; // Jakarta coords
  double _gpsLng = 106.8456;
  double _gpsAlt = 15.2;

  final List<Map<String, dynamic>> _mockAccelSamples = [];
  final List<Map<String, dynamic>> _mockAccelLogSamples = [];
  final List<Map<String, dynamic>> _mockGpsLogSamples = [];

  void _initMockData() {
    final now = DateTime.now();
    // Pre-populate with 12 clean samples
    for (int i = 11; i >= 0; i--) {
      final time = now.subtract(Duration(seconds: i * 5));
      final timeStr = "${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}:${time.second.toString().padLeft(2, '0')}";

      // Gravity baseline: ~9.8 on Z-axis, ~0 on X and Y
      double rx = (Random().nextDouble() - 0.5) * 0.1;
      double ry = (Random().nextDouble() - 0.5) * 0.1;
      double rz = 9.8 + (Random().nextDouble() - 0.5) * 0.1;
      double magnitude = 0.05; // Idle magnitude

      _mockAccelSamples.add({
        'time': timeStr,
        'x': rx,
        'y': ry,
        'z': rz - 9.8, // relative acceleration
        'magnitude': magnitude,
      });
    }

    // Pre-populate GPS log
    for (int i = 5; i >= 0; i--) {
      final time = now.subtract(Duration(seconds: i * 15));
      final timeStr = "${time.hour.toString().padLeft(2, '0')}:${time.minute.toString().padLeft(2, '0')}:${time.second.toString().padLeft(2, '0')}";
      _mockGpsLogSamples.add({
        'time': timeStr,
        'latitude': _gpsLat + (Random().nextDouble() - 0.5) * 0.0001,
        'longitude': _gpsLng + (Random().nextDouble() - 0.5) * 0.0001,
        'altitude': _gpsAlt + (Random().nextDouble() - 0.5) * 0.5,
        'satellites': 8 + Random().nextInt(4),
        'status': '3D FIX',
      });
    }
  }

  void _generateNextMockSample() {
    final now = DateTime.now();
    final timeStr = "${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}:${now.second.toString().padLeft(2, '0')}";

    if (!_mockAccelPower) {
      // Sensor off
      _mockAccelSamples.removeAt(0);
      _mockAccelSamples.add({
        'time': timeStr,
        'x': 0.0,
        'y': 0.0,
        'z': 0.0,
        'magnitude': 0.0,
      });
      return;
    }

    // Every once in a while, simulate a medium/high tremor spike!
    // A 1-in-8 chance of generating a vibration
    bool isSeismicEvent = Random().nextInt(10) == 0;
    
    double baseRange = 0.15;
    if (isSeismicEvent) {
      // Simulate earthquake based on sensitivity sliders
      // Higher sensitivity threshold settings (multiplier) create larger magnitude spikes
      double sensitivityScale = (_mockAccelSensitivity['x']! + _mockAccelSensitivity['y']! + _mockAccelSensitivity['z']!) / 12.0;
      baseRange = (3.0 + Random().nextDouble() * 15.0) * sensitivityScale;
    }

    double rx = (Random().nextDouble() - 0.5) * baseRange;
    double ry = (Random().nextDouble() - 0.5) * baseRange;
    double rz = (Random().nextDouble() - 0.5) * baseRange;
    double magnitude = sqrt(rx * rx + ry * ry + rz * rz);

    // Keep it realistic: relative PGA value
    final newSample = {
      'time': timeStr,
      'x': rx,
      'y': ry,
      'z': rz,
      'magnitude': magnitude,
    };

    _mockAccelSamples.removeAt(0);
    _mockAccelSamples.add(newSample);

    // If magnitude is above threshold (0.34 MMI I), write to log samples
    if (magnitude >= 0.34) {
      final mmi = _getMmiLevel(magnitude);
      final logEntry = {
        'time': timeStr,
        'x': rx,
        'y': ry,
        'z': rz,
        'magnitude': magnitude,
        'mmi_level': mmi['level'],
        'mmi_status': mmi['status'],
        'mmi_color': mmi['color'],
      };

      _mockAccelLogSamples.add(logEntry);
      if (_mockAccelLogSamples.length > 15) {
        _mockAccelLogSamples.removeAt(0);
      }
    }

    // Simulate GPS drift if GPS is on
    if (_mockGpsPower) {
      _gpsLat += (Random().nextDouble() - 0.5) * 0.00004;
      _gpsLng += (Random().nextDouble() - 0.5) * 0.00004;
      _gpsAlt += (Random().nextDouble() - 0.5) * 0.1;

      // Add to GPS Log occasionally
      if (Random().nextInt(3) == 0) {
        _mockGpsLogSamples.add({
          'time': timeStr,
          'latitude': _gpsLat,
          'longitude': _gpsLng,
          'altitude': _gpsAlt,
          'satellites': 9 + Random().nextInt(4),
          'status': '3D FIX',
        });
        if (_mockGpsLogSamples.length > 10) {
          _mockGpsLogSamples.removeAt(0);
        }
      }
    }
  }

  Map<String, String> _getMmiLevel(double magnitude) {
    if (magnitude < 0.34) {
      return {'level': 'I', 'status': 'Aman', 'color': '#22c55e'};
    } else if (magnitude < 2.8) {
      return {'level': 'II-III', 'status': 'Lemah', 'color': '#86efac'};
    } else if (magnitude < 7.8) {
      return {'level': 'IV', 'status': 'Waspada', 'color': '#f59e0b'};
    } else if (magnitude < 18.4) {
      return {'level': 'V', 'status': 'Bahaya!', 'color': '#f97316'};
    } else {
      return {'level': 'VI+', 'status': 'AWAS!', 'color': '#ef4444'};
    }
  }

  Map<String, dynamic> _getMockDashboardJson() {
    final now = DateTime.now();
    final dateStr = "${now.day} May ${now.year}";

    final latestAccel = _mockAccelSamples.last;
    final maxMag = _mockAccelSamples.map((s) => s['magnitude'] as double).reduce(max);
    final avgMag = _mockAccelSamples.map((s) => s['magnitude'] as double).reduce((a, b) => a + b) / _mockAccelSamples.length;

    return {
      'gps': {
        'latitude': _mockGpsPower ? _gpsLat : 0.0,
        'longitude': _mockGpsPower ? _gpsLng : 0.0,
        'altitude': _mockGpsPower ? _gpsAlt : 0.0,
        'satellites': _mockGpsPower ? 10 : 0,
        'status': _mockGpsPower ? '3D FIX' : 'NO FIX',
        'recorded_at': _mockGpsPower ? "$dateStr ${latestAccel['time']} WIB" : '--',
        'is_connected': _mockGpsPower,
      },
      'currentAccel': {
        'x': latestAccel['x'],
        'y': latestAccel['y'],
        'z': latestAccel['z'],
        'magnitude': latestAccel['magnitude'],
        'time': latestAccel['time'],
        'sensor_time': latestAccel['time'],
        'is_connected': _mockAccelPower,
      },
      'accelSamples': _mockAccelSamples,
      'accelLogSamples': _mockAccelLogSamples,
      'gpsLogSamples': _mockGpsLogSamples,
      'summary': {
        'maximum': maxMag,
        'average': avgMag,
        'count': _mockAccelSamples.length,
      },
      'lastUpdatedAt': "$dateStr ${latestAccel['time']} WIB",
    };
  }
}
