import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class NotificationService {
  static final NotificationService _instance = NotificationService._internal();
  factory NotificationService() => _instance;
  NotificationService._internal();

  final FlutterLocalNotificationsPlugin flutterLocalNotificationsPlugin = FlutterLocalNotificationsPlugin();

  Future<void> init() async {
    const AndroidInitializationSettings initializationSettingsAndroid = AndroidInitializationSettings('@mipmap/ic_launcher');
    
    // We don't configure iOS for now, but usually it's DarwinInitializationSettings
    const InitializationSettings initializationSettings = InitializationSettings(
      android: initializationSettingsAndroid,
    );

    await flutterLocalNotificationsPlugin.initialize(
      settings: initializationSettings,
      onDidReceiveNotificationResponse: (NotificationResponse response) async {
        // Handle notification tapped logic here if needed
      },
    );

    // Create the notification channel specifically for alarms so it has high importance and custom sound
    await _createAlarmChannel();
  }

  Future<void> _createAlarmChannel() async {
    final AndroidNotificationChannel channel = AndroidNotificationChannel(
      'earthquake_alarm_channel', // id
      'Earthquake Alerts', // name
      description: 'High priority alerts for detected earthquakes.',
      importance: Importance.max,
      playSound: true,
      sound: const RawResourceAndroidNotificationSound('alarm'), // Refers to res/raw/alarm.wav
      enableVibration: true,
      vibrationPattern: Int64List.fromList([0, 1000, 500, 1000, 500, 1000]),
    );

    await flutterLocalNotificationsPlugin
        .resolvePlatformSpecificImplementation<AndroidFlutterLocalNotificationsPlugin>()
        ?.createNotificationChannel(channel);
  }

  Future<void> showEarthquakeAlert(String title, String body) async {
    final AndroidNotificationDetails androidPlatformChannelSpecifics = AndroidNotificationDetails(
      'earthquake_alarm_channel', // channel id MUST MATCH created channel
      'Earthquake Alerts',
      channelDescription: 'High priority alerts for detected earthquakes.',
      importance: Importance.max,
      priority: Priority.high,
      playSound: true,
      sound: const RawResourceAndroidNotificationSound('alarm'),
      enableVibration: true,
      vibrationPattern: Int64List.fromList([0, 1000, 500, 1000, 500, 1000]),
      styleInformation: const BigTextStyleInformation(''),
      color: const Color(0xFFEF4444),
    );

    final NotificationDetails platformChannelSpecifics = NotificationDetails(
      android: androidPlatformChannelSpecifics,
    );

    await flutterLocalNotificationsPlugin.show(
      id: 0, // notification id
      title: title,
      body: body,
      notificationDetails: platformChannelSpecifics,
    );
  }
}
