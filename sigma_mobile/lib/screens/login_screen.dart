import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'register_screen.dart';
import 'main_navigation.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  
  bool _isLoading = false;
  bool _obscurePassword = true;
  String _errorMessage = "";
  
  final ApiService _apiService = ApiService();

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  Future<void> _handleLogin() async {
    if (!_formKey.currentState!.validate()) return;
    
    setState(() {
      _isLoading = true;
      _errorMessage = "";
    });

    try {
      final success = await _apiService.login(
        _emailController.text.trim(),
        _passwordController.text,
      );

      if (success && mounted) {
        Navigator.of(context).pushReplacement(
          MaterialPageRoute(builder: (context) => const MainNavigation()),
        );
      }
    } catch (e) {
      setState(() {
        _errorMessage = e.toString().replaceAll("Exception:", "").trim();
      });
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  void _showServerSettings() {
    final serverController = TextEditingController(text: _apiService.serverUrl);
    bool isMock = _apiService.isMockMode;

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: const Color(0xFF1B1613),
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) => StatefulBuilder(
        builder: (context, setModalState) => Padding(
          padding: EdgeInsets.only(
            top: 20,
            left: 20,
            right: 20,
            bottom: MediaQuery.of(context).viewInsets.bottom + 20,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    "Konfigurasi Server & Mode",
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFFF1EAE5),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.grey),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
              const SizedBox(height: 15),
              SwitchListTile(
                title: const Text("Mode Demo (Offline Mock)"),
                subtitle: const Text("Simulasi telemetry gempa & GPS tanpa koneksi server"),
                value: isMock,
                activeThumbColor: const Color(0xFFC2743E),
                onChanged: (val) {
                  setModalState(() {
                    isMock = val;
                  });
                },
              ),
              const Divider(color: Colors.white10),
              if (!isMock) ...[
                const SizedBox(height: 10),
                const Text(
                  "URL Server Laravel (API)",
                  style: TextStyle(color: Color(0xFFC2743E), fontWeight: FontWeight.bold),
                ),
                const SizedBox(height: 8),
                TextField(
                  controller: serverController,
                  decoration: const InputDecoration(
                    hintText: "http://192.168.1.100:8000",
                    prefixIcon: Icon(Icons.dns, color: Color(0xFFC2743E)),
                  ),
                ),
              ],
              const SizedBox(height: 20),
              ElevatedButton(
                onPressed: () async {
                  await _apiService.setMockMode(isMock);
                  if (!isMock) {
                    await _apiService.setServerUrl(serverController.text.trim());
                  }
                  if (mounted) Navigator.pop(context);
                  setState(() {}); // Refresh login state
                },
                child: const Text("Simpan Konfigurasi"),
              ),
            ],
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;

    return Scaffold(
      body: Stack(
        children: [
          // Background Gradient decoration representing seismic waves / glowing center
          Container(
            width: double.infinity,
            height: double.infinity,
            decoration: const BoxDecoration(
              gradient: RadialGradient(
                center: Alignment(0, -0.3),
                radius: 1.2,
                colors: [
                  Color(0xFF2E170C), // Deep amber brown glow
                  Color(0xFF0F0E0D), // Solid deep charcoal black
                ],
                stops: [0.0, 0.7],
              ),
            ),
          ),
          
          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24.0),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    // LOGO Icon
                    Center(
                      child: Container(
                        width: 80,
                        height: 80,
                        decoration: BoxDecoration(
                          color: const Color(0xFFC2743E).withOpacity(0.1),
                          shape: BoxShape.circle,
                          border: Border.all(
                            color: const Color(0xFFC2743E),
                            width: 2,
                          ),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFFC2743E).withOpacity(0.2),
                              blurRadius: 15,
                              spreadRadius: 2,
                            ),
                          ],
                        ),
                        child: const Icon(
                          Icons.radar_rounded,
                          size: 40,
                          color: Color(0xFFC2743E),
                        ),
                      ),
                    ),
                    const SizedBox(height: 20),
                    
                    // Title
                    const Center(
                      child: Text(
                        "SIGMA",
                        style: TextStyle(
                          fontSize: 36,
                          fontWeight: FontWeight.w900,
                          color: Colors.white,
                          letterSpacing: 2,
                        ),
                      ),
                    ),
                    const Center(
                      child: Text(
                        "Seismic & GPS Monitoring System",
                        style: TextStyle(
                          fontSize: 14,
                          color: Colors.grey,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ),
                    
                    const SizedBox(height: 40),
                    
                    // Glassmorphic Login Card
                    Container(
                      padding: const EdgeInsets.all(24),
                      decoration: BoxDecoration(
                        color: const Color(0xFF1B1613).withOpacity(0.8),
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: const Color(0xFFC2743E).withOpacity(0.15),
                          width: 1.5,
                        ),
                      ),
                      child: Form(
                        key: _formKey,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            Text(
                              _apiService.isMockMode ? "Mode Demo Aktif" : "Masuk ke Akun",
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFFF1EAE5),
                              ),
                              textAlign: TextAlign.center,
                            ),
                            if (_apiService.isMockMode) ...[
                              const SizedBox(height: 4),
                              const Text(
                                "(Gunakan email & password bebas untuk mencoba)",
                                style: TextStyle(fontSize: 12, color: Colors.amber, fontWeight: FontWeight.w500),
                                textAlign: TextAlign.center,
                              ),
                            ],
                            const SizedBox(height: 20),
                            
                            // Email Field
                            TextFormField(
                              controller: _emailController,
                              keyboardType: TextInputType.emailAddress,
                              decoration: const InputDecoration(
                                labelText: "Alamat Email",
                                prefixIcon: Icon(Icons.email_outlined),
                              ),
                              validator: (value) {
                                if (value == null || value.trim().isEmpty) {
                                  return "Email tidak boleh kosong.";
                                }
                                if (!RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$').hasMatch(value)) {
                                  return "Alamat email tidak valid.";
                                }
                                return null;
                              },
                            ),
                            const SizedBox(height: 16),
                            
                            // Password Field
                            TextFormField(
                              controller: _passwordController,
                              obscureText: _obscurePassword,
                              decoration: InputDecoration(
                                labelText: "Kata Sandi",
                                prefixIcon: const Icon(Icons.lock_outlined),
                                suffixIcon: IconButton(
                                  icon: Icon(
                                    _obscurePassword ? Icons.visibility_off : Icons.visibility,
                                    color: Colors.grey,
                                  ),
                                  onPressed: () {
                                    setState(() {
                                      _obscurePassword = !_obscurePassword;
                                    });
                                  },
                                ),
                              ),
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return "Password tidak boleh kosong.";
                                }
                                if (value.length < 6) {
                                  return "Password minimal 6 karakter.";
                                }
                                return null;
                              },
                            ),
                            
                            if (_errorMessage.isNotEmpty) ...[
                              const SizedBox(height: 16),
                              Container(
                                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                                decoration: BoxDecoration(
                                  color: Colors.red.withOpacity(0.1),
                                  borderRadius: BorderRadius.circular(8),
                                  border: Border.all(color: Colors.red.withOpacity(0.3)),
                                ),
                                child: Text(
                                  _errorMessage,
                                  style: const TextStyle(color: Color(0xFFEF4444), fontSize: 13),
                                  textAlign: TextAlign.center,
                                ),
                              ),
                            ],
                            
                            const SizedBox(height: 24),
                            
                            // Login Button
                            ElevatedButton(
                              onPressed: _isLoading ? null : _handleLogin,
                              child: _isLoading
                                  ? const SizedBox(
                                      height: 20,
                                      width: 20,
                                      child: CircularProgressIndicator(
                                        color: Colors.white,
                                        strokeWidth: 2,
                                      ),
                                    )
                                  : const Text("MASUK"),
                            ),
                          ],
                        ),
                      ),
                    ),
                    
                    const SizedBox(height: 24),
                    
                    // Register Redirection
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        const Text(
                          "Belum memiliki akun?",
                          style: TextStyle(color: Colors.grey),
                        ),
                        TextButton(
                          onPressed: () {
                            Navigator.of(context).push(
                              MaterialPageRoute(builder: (context) => const RegisterScreen()),
                            );
                          },
                          child: const Text("Daftar Sekarang"),
                        ),
                      ],
                    ),
                    
                    // Server Settings / Mode trigger
                    Center(
                      child: TextButton.icon(
                        onPressed: _showServerSettings,
                        icon: const Icon(Icons.settings, size: 16),
                        label: Text(
                          _apiService.isMockMode 
                              ? "Mode: Demo (Offline). Ubah ke API Server" 
                              : "Mode: API Server (${_apiService.serverUrl.replaceAll('http://', '')})",
                          style: const TextStyle(fontSize: 12),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
