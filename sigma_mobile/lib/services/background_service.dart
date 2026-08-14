import 'dart:async';
import 'dart:ui';
import 'package:flutter_background_service/flutter_background_service.dart';
import 'api_service.dart';
import 'notification_service.dart';

Future<void> initializeBackgroundService() async {
  final service = FlutterBackgroundService();

  // Make sure notification channel is initialized before the service starts
  await NotificationService().init();

  await service.configure(
    androidConfiguration: AndroidConfiguration(
      onStart: onStart,
      autoStart: true,
      isForegroundMode: true,
      notificationChannelId: 'earthquake_alarm_channel',
      initialNotificationTitle: 'Sigma Monitoring Aktif',
      initialNotificationContent: 'Memantau sensor gempa di latar belakang',
      foregroundServiceNotificationId: 888,
    ),
    iosConfiguration: IosConfiguration(
      autoStart: true,
      onForeground: onStart,
    ),
  );

  await service.startService();
}

@pragma('vm:entry-point')
void onStart(ServiceInstance service) async {
  // Initialize services in background isolate
  DartPluginRegistrant.ensureInitialized();

  final notificationService = NotificationService();
  await notificationService.init();

  final apiService = ApiService();
  await apiService.init();

  String? lastAlarmTime;

  // Poll every 5 seconds
  Timer.periodic(const Duration(seconds: 5), (timer) async {
    try {
      // Must re-init to get latest token if user logs out/in
      await apiService.init(); 

      if (apiService.isAuthenticated) {
        final data = await apiService.getDashboardData();
        
        if (data.seismicEvents.isNotEmpty) {
          final latestEvent = data.seismicEvents.last;
          final eventTime = (latestEvent['time'] ?? latestEvent['recorded_at'])?.toString();
          final mmiLevel = latestEvent['mmi_level'] as String? ?? 'I';
          final mmiStatus = latestEvent['mmi_status'] as String? ?? 'Aman';
          final magnitude = (latestEvent['magnitude'] as num?)?.toStringAsFixed(2) ?? '0.0';

          if (eventTime != null && lastAlarmTime != eventTime) {
            lastAlarmTime = eventTime;
            
            // Trigger local push notification with alarm
            await notificationService.showEarthquakeAlert(
              "⚠ AWAS GEMPA BUMI! ($mmiStatus)", 
              "Magnitudo: $magnitude (Skala $mmiLevel). Waktu: $eventTime. Harap waspada dan cari tempat aman!"
            );
          }
        }
      }
    } catch (e) {
      // Ignore API errors in background
    }
  });
}
