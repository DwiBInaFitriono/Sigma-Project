import 'package:flutter/material.dart';
import '../services/api_service.dart';
import 'login_screen.dart';

class ProfileTab extends StatefulWidget {
  const ProfileTab({super.key});

  @override
  State<ProfileTab> createState() => _ProfileTabState();
}

class _ProfileTabState extends State<ProfileTab> {
  final ApiService _apiService = ApiService();
  final _serverUrlController = TextEditingController();
  
  bool _isMock = true;
  String _userName = "Operator";
  String _userEmail = "operator@sigma.com";
  String _userRole = "admin";

  @override
  void initState() {
    super.initState();
    _serverUrlController.text = _apiService.serverUrl;
    _isMock = _apiService.isMockMode;
    
    final user = _apiService.currentUser;
    if (user != null) {
      _userName = user.name;
      _userEmail = user.email;
      _userRole = user.role;
    }
  }

  @override
  void dispose() {
    _serverUrlController.dispose();
    super.dispose();
  }

  Future<void> _saveSettings() async {
    final newUrl = _serverUrlController.text.trim();
    if (newUrl.isEmpty && !_isMock) {
      _showFeedback("Alamat Server URL tidak boleh kosong.", isError: true);
      return;
    }

    try {
      await _apiService.setMockMode(_isMock);
      if (!_isMock) {
        await _apiService.setServerUrl(newUrl);
      }
      
      _showFeedback("Konfigurasi server berhasil diperbarui.");
      setState(() {});
    } catch (e) {
      _showFeedback("Gagal memperbarui konfigurasi: $e", isError: true);
    }
  }

  Future<void> _handleLogout() async {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        backgroundColor: const Color(0xFF1B1613),
        title: const Text("Konfirmasi Keluar"),
        content: const Text("Apakah Anda yakin ingin keluar dari aplikasi SIGMA?"),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text("BATAL", style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(context);
              await _apiService.logout();
              if (mounted) {
                Navigator.of(context).pushAndRemoveUntil(
                  MaterialPageRoute(builder: (context) => const LoginScreen()),
                  (route) => false,
                );
              }
            },
            style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFFE13B3B)),
            child: const Text("KELUAR"),
          ),
        ],
      ),
    );
  }

  void _showFeedback(String msg, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg, style: const TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: isError ? Colors.red : const Color(0xFFC2743E),
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF0F0E0D),
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        title: const Text(
          "PENGATURAN & AKUN",
          style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 0.8),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Header kicker
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  "Profil & Parameter",
                  style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.white),
                ),
                Text(
                  "Kelola informasi identitas Anda dan tautan API sistem.",
                  style: TextStyle(fontSize: 12, color: const Color(0xFFC6B9AE).withOpacity(0.7)),
                ),
              ],
            ),
            const SizedBox(height: 25),

            // USER PROFILE CARD
            Card(
              child: Padding(
                padding: const EdgeInsets.all(20.0),
                child: Row(
                  children: [
                    Container(
                      width: 60,
                      height: 60,
                      decoration: BoxDecoration(
                        color: const Color(0xFFC2743E).withOpacity(0.1),
                        shape: BoxShape.circle,
                        border: Border.all(color: const Color(0xFFC2743E), width: 1.5),
                      ),
                      child: const Icon(Icons.person, size: 32, color: Color(0xFFC2743E)),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _userName,
                            style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold, color: Colors.white),
                          ),
                          Text(
                            _userEmail,
                            style: TextStyle(fontSize: 12, color: Colors.grey[400]),
                          ),
                          const SizedBox(height: 6),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
                            decoration: BoxDecoration(
                              color: const Color(0xFFC2743E).withOpacity(0.15),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: Text(
                              "Role: ${_userRole.toUpperCase()}",
                              style: const TextStyle(fontSize: 9, color: Color(0xFFC2743E), fontWeight: FontWeight.bold),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 20),

            // SYSTEM CONFIGURATIONS
            Card(
              child: Padding(
                padding: const EdgeInsets.all(18.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    const Row(
                      children: [
                        Icon(Icons.settings_outlined, color: Color(0xFFC2743E), size: 20),
                        SizedBox(width: 10),
                        Text("Koneksi API & Integrasi", style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
                      ],
                    ),
                    const Divider(height: 30, color: Colors.white10),
                    
                    // Mock Toggle Switch
                    SwitchListTile(
                      title: const Text("Mode Demo (Offline Mock)"),
                      subtitle: const Text("Gunakan simulasi data lokal tanpa memerlukan server aktif"),
                      value: _isMock,
                      activeColor: const Color(0xFFC2743E),
                      contentPadding: EdgeInsets.zero,
                      onChanged: (val) {
                        setState(() {
                          _isMock = val;
                        });
                      },
                    ),
                    const Divider(color: Colors.white10),
                    
                    // Server URL Input
                    if (!_isMock) ...[
                      const SizedBox(height: 10),
                      const Text(
                        "URL Endpoint API Laravel",
                        style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFFC2743E)),
                      ),
                      const SizedBox(height: 8),
                      TextField(
                        controller: _serverUrlController,
                        decoration: const InputDecoration(
                          hintText: "http://192.168.1.100:8000",
                          prefixIcon: Icon(Icons.dns_outlined, size: 20),
                        ),
                        keyboardType: TextInputType.url,
                      ),
                      const SizedBox(height: 20),
                    ],

                    ElevatedButton.icon(
                      onPressed: _saveSettings,
                      icon: const Icon(Icons.check, size: 18),
                      label: const Text("SIMPAN PENGATURAN"),
                    ),
                  ],
                ),
              ),
            ),
            
            const SizedBox(height: 20),

            // LOGOUT ACTION
            Card(
              color: Colors.red.withOpacity(0.04),
              child: ListTile(
                leading: const Icon(Icons.logout_rounded, color: Color(0xFFEF4444)),
                title: const Text(
                  "Keluar dari Aplikasi",
                  style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white),
                ),
                subtitle: const Text(
                  "Hapus token sesi dari perangkat",
                  style: TextStyle(fontSize: 11, color: Colors.grey),
                ),
                onTap: _handleLogout,
              ),
            ),
            
            const SizedBox(height: 100),
          ],
        ),
      ),
    );
  }
}
