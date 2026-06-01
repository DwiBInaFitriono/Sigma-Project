import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:intl/intl.dart';
import 'package:audioplayers/audioplayers.dart';
import '../services/api_service.dart';
import '../models/telemetry.dart';

class DashboardTab extends StatefulWidget {
  const DashboardTab({super.key});

  @override
  State<DashboardTab> createState() => _DashboardTabState();
}

class _DashboardTabState extends State<DashboardTab> {
  final ApiService _apiService = ApiService();
  
  Timer? _pollingTimer;
  Timer? _clockTimer;
  
  String _timeString = "";
  String _dateString = "";
  
  DashboardData? _dashboardData;
  bool _isLoading = true;
  String _errorMsg = "";
  
  // Map Controller
  final MapController _mapController = MapController();

  // Audio Player
  final AudioPlayer _audioPlayer = AudioPlayer();
  String? _lastAlarmTime;

  @override
  void initState() {
    super.initState();
    _updateClock();
    _clockTimer = Timer.periodic(const Duration(seconds: 1), (timer) => _updateClock());
    
    // Start polling data every 3 seconds (tighter loop for visual animation on mobile)
    _fetchData();
    _pollingTimer = Timer.periodic(const Duration(seconds: 3), (timer) => _fetchData());
  }

  @override
  void dispose() {
    _pollingTimer?.cancel();
    _clockTimer?.cancel();
    _mapController.dispose();
    _audioPlayer.dispose();
    super.dispose();
  }

  void _updateClock() {
    final now = DateTime.now();
    final timeStr = DateFormat('HH:mm:ss').format(now);
    
    // Indonesian translated date format
    final days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    final months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    final dateStr = "${days[now.weekday % 7]}, ${now.day.toString().padLeft(2, '0')} ${months[now.month - 1]} ${now.year}";

    if (mounted) {
      setState(() {
        _timeString = timeStr;
        _dateString = dateStr;
      });
    }
  }

  Future<void> _fetchData() async {
    try {
      final data = await _apiService.getDashboardData();
      if (mounted) {
        setState(() {
          _dashboardData = data;
          _isLoading = false;
          _errorMsg = "";
        });

        // Trigger map center fly-to / pan
        if (data.gps.isConnected && data.gps.latitude != 0.0 && data.gps.longitude != 0.0) {
          _mapController.move(LatLng(data.gps.latitude, data.gps.longitude), _mapController.camera.zoom);
        }

        // Trigger alarm if there is a new seismic event
        if (data.seismicEvents.isNotEmpty) {
          final latestEvent = data.seismicEvents.last;
          final eventTime = latestEvent['time'] as String;
          if (_lastAlarmTime != eventTime) {
            _lastAlarmTime = eventTime;
            // Play alarm sound
            _audioPlayer.play(AssetSource('sounds/alarm.wav'));
          }
        }
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMsg = e.toString().replaceAll("Exception:", "").trim();
          _isLoading = false;
        });
      }
    }
  }

  // Get MMI styles based on magnitude
  Color _getMmiColor(dynamic val) {
    double mag = 0.0;
    if (val is num) {
      mag = val.toDouble();
    } else if (val is String) {
      mag = double.tryParse(val) ?? 0.0;
    }
    
    if (mag < 0.15) return const Color(0xFF22C55E);
    if (mag < 0.30) return const Color(0xFF86EFAC);
    if (mag < 0.60) return const Color(0xFFF59E0B);
    if (mag < 1.00) return const Color(0xFFF97316);
    return const Color(0xFFEF4444);
  }

  String _getMmiLevel(double mag) {
    if (mag < 0.15) return 'I (Aman)';
    if (mag < 0.30) return 'II-III (Lemah)';
    if (mag < 0.60) return 'IV (Waspada)';
    if (mag < 1.00) return 'V (Bahaya!)';
    return 'VI+ (AWAS!)';
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading && _dashboardData == null) {
      return const Scaffold(
        body: Center(
          child: CircularProgressIndicator(color: Color(0xFFC2743E)),
        ),
      );
    }

    final gps = _dashboardData?.gps;
    final accel = _dashboardData?.currentAccel;
    final samples = _dashboardData?.accelSamples ?? [];
    final summary = _dashboardData?.summary;
    final accelLog = _dashboardData?.accelLogSamples ?? [];
    final gpsLog = _dashboardData?.gpsLogSamples ?? [];

    double currentMagnitude = (accel?['magnitude'] as num?)?.toDouble() ?? 0.0;

    // Calculate Y-axis range dynamically to avoid overflow and support both m/s2 and Gal data
    double minYVal = -5.0;
    double maxYVal = 15.0;
    if (samples.isNotEmpty) {
      double minSample = samples.map((s) {
        double m = s.magnitude > 20.0 ? s.magnitude / 100.0 : s.magnitude;
        return [s.x, s.y, s.z, m].reduce((a, b) => a < b ? a : b);
      }).reduce((a, b) => a < b ? a : b);
      
      double maxSample = samples.map((s) {
        double m = s.magnitude > 20.0 ? s.magnitude / 100.0 : s.magnitude;
        return [s.x, s.y, s.z, m].reduce((a, b) => a > b ? a : b);
      }).reduce((a, b) => a > b ? a : b);

      double range = maxSample - minSample;
      if (range < 4.0) range = 4.0;
      minYVal = minSample - (range * 0.1);
      maxYVal = maxSample + (range * 0.1);
      
      // Keep it within a reasonable boundary
      if (minYVal < -40.0) minYVal = -40.0;
      if (maxYVal > 50.0) maxYVal = 50.0;
    }

    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF0F0E0D),
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        title: Row(
          children: [
            const Icon(Icons.radar_rounded, color: Color(0xFFC2743E)),
            const SizedBox(width: 10),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  "SIGMA MONITORING",
                  style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 0.8),
                ),
                Text(
                  _apiService.isMockMode ? "Mode Demo (Offline)" : "Koneksi Live Server",
                  style: TextStyle(fontSize: 10, color: _apiService.isMockMode ? Colors.amber : Colors.green),
                ),
              ],
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh, color: Colors.white70),
            onPressed: () {
              setState(() => _isLoading = true);
              _fetchData();
            },
          ),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _fetchData,
        color: const Color(0xFFC2743E),
        backgroundColor: const Color(0xFF1B1613),
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // HEADER CLOCK / TIME
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          "Panel Utama",
                          style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.white),
                        ),
                        Text(
                          "Pantau getaran gempa & GPS realtime.",
                          style: TextStyle(fontSize: 12, color: const Color(0xFFC6B9AE).withOpacity(0.7)),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                    decoration: BoxDecoration(
                      color: const Color(0xFF1B1613),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFC2743E).withOpacity(0.1)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.end,
                      children: [
                        Text(
                          _timeString,
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800, color: Color(0xFFC2743E), fontFamily: 'monospace'),
                        ),
                        Text(
                          _dateString,
                          style: const TextStyle(fontSize: 9, color: Colors.grey),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 20),

              if (_errorMsg.isNotEmpty) ...[
                Container(
                  margin: const EdgeInsets.only(bottom: 20),
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.red.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.red.withOpacity(0.2)),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.error_outline, color: Colors.red),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          "Gagal sinkron: $_errorMsg",
                          style: const TextStyle(color: Color(0xFFEF4444), fontSize: 12),
                        ),
                      ),
                    ],
                  ),
                ),
              ],

              // SUMMARY CARDS ROW
              GridView.count(
                crossAxisCount: 3,
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                mainAxisSpacing: 10,
                crossAxisSpacing: 10,
                childAspectRatio: 1.0,
                children: [
                  _buildGlowCard(
                    "Status Sinkron",
                    _apiService.isMockMode ? "Live Demo" : "Live Server",
                    "Update 3 detik",
                    icon: Icons.sync,
                    iconColor: Colors.blue,
                  ),
                  _buildGlowCard(
                    "Magnitudo PGA",
                    currentMagnitude.toStringAsFixed(2),
                    _getMmiLevel(currentMagnitude),
                    icon: Icons.waves_rounded,
                    iconColor: _getMmiColor(currentMagnitude),
                    valueColor: _getMmiColor(currentMagnitude),
                  ),
                    _buildGlowCard(
                      "Update Terakhir",
                      (_dashboardData?.lastUpdatedAt.contains(' ') ?? false) ? _dashboardData!.lastUpdatedAt.split(' ')[1] : '--:--:--',
                      (_dashboardData?.lastUpdatedAt.isNotEmpty ?? false) ? _dashboardData!.lastUpdatedAt.split(' ').first : '--/--/--',
                      icon: Icons.access_time_rounded,
                      iconColor: const Color(0xFFC2743E),
                    ),
                ],
              ),
              const SizedBox(height: 20),

              // CHART SECTION
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                "Sensor ADXL345 (Akselerometer)",
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                              ),
                              Text(
                                "Nilai X / Y / Z: ${(accel?['x'] as num?)?.toStringAsFixed(2) ?? '0.00'} / ${(accel?['y'] as num?)?.toStringAsFixed(2) ?? '0.00'} / ${(accel?['z'] as num?)?.toStringAsFixed(2) ?? '0.00'}",
                                style: const TextStyle(fontSize: 12, color: Colors.grey),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFFE13B3B).withOpacity(0.1),
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(color: const Color(0xFFE13B3B).withOpacity(0.2)),
                            ),
                            child: const Text(
                              "Realtime",
                              style: TextStyle(fontSize: 10, color: Color(0xFFE13B3B), fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 20),
                      
                      // LINE CHART CONTAINER
                      SizedBox(
                        height: 200,
                        child: samples.isEmpty
                            ? const Center(child: Text("Menunggu sampel sensor..."))
                            : LineChart(
                                LineChartData(
                                  clipData: const FlClipData.all(),
                                  gridData: FlGridData(
                                    show: true,
                                    drawVerticalLine: true,
                                    horizontalInterval: 2.0,
                                    verticalInterval: 1.0,
                                    getDrawingHorizontalLine: (value) => FlLine(
                                      color: const Color(0xFFC2743E).withOpacity(0.08),
                                      strokeWidth: 1,
                                    ),
                                    getDrawingVerticalLine: (value) => FlLine(
                                      color: const Color(0xFFC2743E).withOpacity(0.08),
                                      strokeWidth: 1,
                                    ),
                                  ),
                                  titlesData: const FlTitlesData(
                                    show: true,
                                    rightTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                                    topTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                                    bottomTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                                    leftTitles: AxisTitles(
                                      sideTitles: SideTitles(
                                        showTitles: true,
                                        interval: 4,
                                        reservedSize: 28,
                                      ),
                                    ),
                                  ),
                                  borderData: FlBorderData(show: false),
                                  minX: 0,
                                  maxX: (samples.length - 1).toDouble(),
                                  minY: minYVal,
                                  maxY: maxYVal,
                                  lineBarsData: [
                                    _buildBarData(samples, (s) => s.x, const Color(0xFFB45309), 1.5), // X (brown)
                                    _buildBarData(samples, (s) => s.y, const Color(0xFFF97316), 1.5), // Y (orange)
                                    _buildBarData(samples, (s) => s.z, const Color(0xFFFBBF24), 1.5), // Z (yellow)
                                    _buildBarData(samples, (s) => s.magnitude > 20.0 ? s.magnitude / 100.0 : s.magnitude, const Color(0xFFE13B3B), 2.5, isFilled: true), // Magnitude (red)
                                  ],
                                ),
                              ),
                      ),
                      const SizedBox(height: 15),

                      // Chart Legend
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          _buildLegendItem("X", const Color(0xFFB45309)),
                          const SizedBox(width: 12),
                          _buildLegendItem("Y", const Color(0xFFF97316)),
                          const SizedBox(width: 12),
                          _buildLegendItem("Z", const Color(0xFFFBBF24)),
                          const SizedBox(width: 12),
                          _buildLegendItem("Magnitudo", const Color(0xFFE13B3B)),
                        ],
                      ),
                      const SizedBox(height: 15),

                      // SUMMARY STATISTICS ROW
                      Row(
                        children: [
                          Expanded(
                            child: _buildStatSubCard("Max Mag", summary?.maximum.toStringAsFixed(3) ?? "0.000"),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _buildStatSubCard("Rata-rata", summary?.average.toStringAsFixed(3) ?? "0.000"),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: _buildStatSubCard("Sampel", "${summary?.count ?? 0}"),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // GPS MAP SECTION
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text(
                                "GPS NEO-6M (Lokasi Perangkat)",
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                              ),
                              Text(
                                "Status: ${gps?.status ?? 'NO FIX'}",
                                style: const TextStyle(fontSize: 12, color: Colors.grey),
                              ),
                            ],
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: (gps?.isConnected ?? false) ? Colors.green.withOpacity(0.1) : Colors.red.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(color: (gps?.isConnected ?? false) ? Colors.green.withOpacity(0.2) : Colors.red.withOpacity(0.2)),
                            ),
                            child: Text(
                              (gps?.isConnected ?? false) ? "Online" : "Offline",
                              style: TextStyle(fontSize: 10, color: (gps?.isConnected ?? false) ? Colors.green : Colors.red, fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 15),

                      // MAP BOX
                      ClipRRect(
                        borderRadius: BorderRadius.circular(12),
                        child: SizedBox(
                          height: 220,
                          child: (gps != null && gps.latitude != 0 && gps.longitude != 0)
                              ? FlutterMap(
                                  mapController: _mapController,
                                  options: MapOptions(
                                    initialCenter: LatLng(gps.latitude, gps.longitude),
                                    initialZoom: 15.0,
                                    interactionOptions: const InteractionOptions(flags: InteractiveFlag.all),
                                  ),
                                  children: [
                                    TileLayer(
                                      urlTemplate: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                                      userAgentPackageName: 'com.sigma.app',
                                    ),
                                    MarkerLayer(
                                      markers: [
                                        Marker(
                                          width: 32.0,
                                          height: 32.0,
                                          point: LatLng(gps.latitude, gps.longitude),
                                          child: Stack(
                                            alignment: Alignment.center,
                                            children: [
                                              Container(
                                                width: 32,
                                                height: 32,
                                                decoration: BoxDecoration(
                                                  color: const Color(0xFFC2743E).withOpacity(0.2),
                                                  shape: BoxShape.circle,
                                                ),
                                              ),
                                              Container(
                                                width: 14,
                                                height: 14,
                                                decoration: const BoxDecoration(
                                                  color: Colors.white,
                                                  shape: BoxShape.circle,
                                                ),
                                              ),
                                              Container(
                                                width: 8,
                                                height: 8,
                                                decoration: const BoxDecoration(
                                                  color: Color(0xFFC2743E),
                                                  shape: BoxShape.circle,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                )
                              : const Center(
                                  child: Column(
                                    mainAxisAlignment: MainAxisAlignment.center,
                                    children: [
                                      Icon(Icons.map_outlined, size: 40, color: Colors.grey),
                                      SizedBox(height: 10),
                                      Text("Sinyal GPS Terputus / No Fix Lokasi"),
                                    ],
                                  ),
                                ),
                        ),
                      ),
                      const SizedBox(height: 15),

                      // GPS DETAIL DATA GRID
                      GridView.count(
                        crossAxisCount: 3,
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        mainAxisSpacing: 8,
                        crossAxisSpacing: 8,
                        childAspectRatio: 1.8,
                        children: [
                          _buildGridInfoItem("Latitude", gps?.latitude.toStringAsFixed(6) ?? "0.000000"),
                          _buildGridInfoItem("Longitude", gps?.longitude.toStringAsFixed(6) ?? "0.000000"),
                          _buildGridInfoItem("Altitude", "${gps?.altitude.toStringAsFixed(1) ?? '0.0'} m"),
                          _buildGridInfoItem("Satelit", "${gps?.satellites ?? 0}"),
                          _buildGridInfoItem("Koneksi", (gps?.isConnected ?? false) ? "Stabil" : "Terputus"),
                          _buildGridInfoItem("Waktu GPS", (gps?.recordedAt.contains(' ') ?? false) ? gps!.recordedAt.split(' ')[1] : '--:--:--'),
                        ],
                      ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // SCROLLING TELEMETRY LOGS (TABLE ACCELEROMETER)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Text(
                        "Log Getaran Terakhir (PGA >= 0.15)",
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 10),
                      accelLog.isEmpty
                          ? const Padding(
                              padding: EdgeInsets.symmetric(vertical: 24),
                              child: Text(
                                "Belum ada getaran signifikan terdeteksi.",
                                textAlign: TextAlign.center,
                                style: TextStyle(color: Colors.grey),
                              ),
                            )
                          : SingleChildScrollView(
                              scrollDirection: Axis.horizontal,
                              child: DataTable(
                                headingRowHeight: 40,
                                dataRowMinHeight: 35,
                                dataRowMaxHeight: 45,
                                columnSpacing: 18,
                                columns: const [
                                  DataColumn(label: Text('Waktu', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                  DataColumn(label: Text('X', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                  DataColumn(label: Text('Y', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                  DataColumn(label: Text('Z', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                  DataColumn(label: Text('Magnitudo', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                  DataColumn(label: Text('Level MMI', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                ],
                                rows: accelLog.reversed.map((sample) {
                                  double mag = (sample['magnitude'] as num).toDouble();
                                  Color levelColor = _getMmiColor(mag);

                                  return DataRow(
                                    cells: [
                                      DataCell(Text(sample['time'] ?? '--:--:--', style: const TextStyle(fontSize: 11, color: Colors.grey))),
                                      DataCell(Text((sample['x'] as num).toStringAsFixed(2), style: const TextStyle(fontSize: 11))),
                                      DataCell(Text((sample['y'] as num).toStringAsFixed(2), style: const TextStyle(fontSize: 11))),
                                      DataCell(Text((sample['z'] as num).toStringAsFixed(2), style: const TextStyle(fontSize: 11))),
                                      DataCell(Text((sample['magnitude'] as num).toStringAsFixed(4), style: const TextStyle(fontSize: 11))),
                                      DataCell(
                                        Text(
                                          sample['mmi_level'] ?? 'I',
                                          style: TextStyle(
                                            color: levelColor,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 11,
                                          ),
                                        ),
                                      ),
                                    ],
                                  );
                                }).toList(),
                              ),
                            ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 20),

              // GPS LOG SECTION
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      const Text(
                        "Log Lokasi GPS Terakhir",
                        style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                      const SizedBox(height: 10),
                      gpsLog.isEmpty
                          ? const Padding(
                              padding: EdgeInsets.symmetric(vertical: 24),
                              child: Text(
                                "Belum ada log GPS masuk.",
                                textAlign: TextAlign.center,
                                style: TextStyle(color: Colors.grey),
                              ),
                            )
                          : SingleChildScrollView(
                              scrollDirection: Axis.horizontal,
                              child: DataTable(
                                headingRowHeight: 40,
                                dataRowMinHeight: 35,
                                dataRowMaxHeight: 45,
                                columnSpacing: 18,
                                columns: const [
                                  DataColumn(label: Text('Waktu', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                  DataColumn(label: Text('Latitude', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                  DataColumn(label: Text('Longitude', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                  DataColumn(label: Text('Altitude', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                  DataColumn(label: Text('Satelit', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                  DataColumn(label: Text('Status', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12))),
                                ],
                                rows: gpsLog.reversed.map((sample) {
                                  bool isFix = (sample['status'] as String).contains('FIX');

                                  return DataRow(
                                    cells: [
                                      DataCell(Text(sample['time'] ?? '--:--:--', style: const TextStyle(fontSize: 11, color: Colors.grey))),
                                      DataCell(Text((sample['latitude'] as num).toStringAsFixed(6), style: const TextStyle(fontSize: 11))),
                                      DataCell(Text((sample['longitude'] as num).toStringAsFixed(6), style: const TextStyle(fontSize: 11))),
                                      DataCell(Text("${(sample['altitude'] as num).toStringAsFixed(1)} m", style: const TextStyle(fontSize: 11))),
                                      DataCell(Text("${sample['satellites'] ?? 0}", style: const TextStyle(fontSize: 11))),
                                      DataCell(
                                        Text(
                                          sample['status'] ?? 'NO FIX',
                                          style: TextStyle(
                                            color: isFix ? const Color(0xFFC2743E) : Colors.grey,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 11,
                                          ),
                                        ),
                                      ),
                                    ],
                                  );
                                }).toList(),
                              ),
                            ),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 30),
            ],
          ),
        ),
      ),
    );
  }

  // ----------------------------------------------------------------
  // Helper builders for custom styled UI components
  // ----------------------------------------------------------------

  Widget _buildGlowCard(String title, String val, String desc, {required IconData icon, required Color iconColor, Color? valueColor}) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFF1B1613),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFC2743E).withOpacity(0.12)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(
                title,
                style: const TextStyle(fontSize: 9, fontWeight: FontWeight.w600, color: Colors.grey),
              ),
              Icon(icon, size: 14, color: iconColor),
            ],
          ),
          const SizedBox(height: 6),
          Text(
            val,
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w800,
              color: valueColor ?? const Color(0xFFF1EAE5),
            ),
          ),
          Text(
            desc,
            style: const TextStyle(fontSize: 8, color: Colors.grey),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildStatSubCard(String title, String val) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: const Color(0xFF13100E),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: const Color(0xFFC2743E).withOpacity(0.05)),
      ),
      child: Column(
        children: [
          Text(
            title,
            style: const TextStyle(fontSize: 9, color: Colors.grey, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 4),
          Text(
            val,
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w800, color: Color(0xFFF1EAE5)),
          ),
        ],
      ),
    );
  }

  Widget _buildGridInfoItem(String title, String val) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
      decoration: BoxDecoration(
        color: const Color(0xFF13100E),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Text(title, style: const TextStyle(fontSize: 8, color: Colors.grey, fontWeight: FontWeight.bold)),
          const SizedBox(height: 2),
          Text(
            val,
            style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.white),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
          ),
        ],
      ),
    );
  }

  Widget _buildLegendItem(String label, Color color) {
    return Row(
      children: [
        Container(width: 8, height: 8, decoration: BoxDecoration(color: color, shape: BoxShape.circle)),
        const SizedBox(width: 5),
        Text(label, style: const TextStyle(fontSize: 10, fontWeight: FontWeight.bold, color: Colors.grey)),
      ],
    );
  }

  LineChartBarData _buildBarData(List<AccelerometerSample> samples, double Function(AccelerometerSample) mapper, Color color, double width, {bool isFilled = false}) {
    List<FlSpot> spots = [];
    for (int i = 0; i < samples.length; i++) {
      spots.add(FlSpot(i.toDouble(), mapper(samples[i])));
    }
    
    return LineChartBarData(
      spots: spots,
      isCurved: true,
      color: color,
      barWidth: width,
      isStrokeCapRound: true,
      dotData: const FlDotData(show: false),
      belowBarData: BarAreaData(
        show: isFilled,
        gradient: LinearGradient(
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
          colors: [
            color.withOpacity(0.3),
            color.withOpacity(0.0),
          ],
        ),
      ),
    );
  }
}
