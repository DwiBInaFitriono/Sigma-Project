import 'package:flutter/material.dart';
import 'package:permission_handler/permission_handler.dart';
import 'services/api_service.dart';
import 'services/background_service.dart';
import 'screens/login_screen.dart';
import 'screens/main_navigation.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Request notification permissions for Android 13+
  await Permission.notification.request();

  // Initialize API and mock configurations
  final apiService = ApiService();
  await apiService.init();

  // Initialize Background Service
  await initializeBackgroundService();
  
  runApp(const SigmaApp());
}

class SigmaApp extends StatelessWidget {
  const SigmaApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'SIGMA Mobile',
      debugShowCheckedModeBanner: false,
      themeMode: ThemeMode.dark, // Default to dark mode matching telemetry style
      darkTheme: ThemeData(
        useMaterial3: true,
        brightness: Brightness.dark,
        scaffoldBackgroundColor: const Color(0xFF0F0E0D), // Ultra dark rich brown-black
        primaryColor: const Color(0xFFC2743E), // Warm amber accent
        colorScheme: const ColorScheme.dark(
          primary: Color(0xFFC2743E),
          secondary: Color(0xFF8B5026),
          tertiary: Color(0xFFE58A47),
          surface: Color(0xFF1E1A17), // Rich card dark brown-grey
          onSurface: Color(0xFFF1EAE5),
          error: Color(0xFFEF4444),
        ),
        cardTheme: CardThemeData(
          color: const Color(0xFF1B1613),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
            side: BorderSide(
              color: const Color(0xFFC2743E).withOpacity(0.15),
              width: 1.5,
            ),
          ),
          elevation: 0,
        ),
        inputDecorationTheme: InputDecorationTheme(
          filled: true,
          fillColor: const Color(0xFF161210),
          labelStyle: TextStyle(color: const Color(0xFFC2743E).withOpacity(0.7)),
          floatingLabelStyle: const TextStyle(color: Color(0xFFC2743E)),
          hintStyle: TextStyle(color: const Color(0xFFF1EAE5).withOpacity(0.3)),
          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 18),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: const Color(0xFFC2743E).withOpacity(0.1)),
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide(color: const Color(0xFFC2743E).withOpacity(0.15)),
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFC2743E), width: 1.5),
          ),
          errorBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: const BorderSide(color: Color(0xFFEF4444), width: 1),
          ),
        ),
        elevatedButtonTheme: ElevatedButtonThemeData(
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFFC2743E),
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 32),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(12),
            ),
            elevation: 4,
            shadowColor: const Color(0xFFC2743E).withOpacity(0.3),
            textStyle: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.bold,
              letterSpacing: 0.5,
            ),
          ),
        ),
        textButtonTheme: TextButtonThemeData(
          style: TextButton.styleFrom(
            foregroundColor: const Color(0xFFE58A47),
            textStyle: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
        textTheme: const TextTheme(
          headlineLarge: TextStyle(fontSize: 32, fontWeight: FontWeight.w800, color: Color(0xFFF1EAE5), letterSpacing: -0.5),
          headlineMedium: TextStyle(fontSize: 24, fontWeight: FontWeight.bold, color: Color(0xFFF1EAE5)),
          titleLarge: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: Color(0xFFF1EAE5)),
          bodyLarge: TextStyle(fontSize: 16, color: Color(0xFFDFD5CD)),
          bodyMedium: TextStyle(fontSize: 14, color: Color(0xFFC6B9AE)),
          labelLarge: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: Color(0xFFC2743E)),
        ),
      ),
      home: ApiService().isAuthenticated ? const MainNavigation() : const LoginScreen(),
    );
  }
}
