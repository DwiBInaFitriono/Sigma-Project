import 'package:flutter/material.dart';
import '../services/api_service.dart';

class ControlPanelTab extends StatefulWidget {
  const ControlPanelTab({super.key});

  @override
  State<ControlPanelTab> createState() => _ControlPanelTabState();
}

class _ControlPanelTabState extends State<ControlPanelTab> {
  final ApiService _apiService = ApiService();

  bool _isResetting = false;

  void _showFeedback(String msg, {bool isError = false}) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(msg, style: const TextStyle(fontWeight: FontWeight.bold)),
        backgroundColor: isError ? Colors.red : const Color(0xFFC2743E),
        behavior: SnackBarBehavior.floating,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  Future<void> _resetEsp32() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: const Color(0xFF1A1917),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.warning_amber_rounded, color: Colors.amber, size: 24),
            SizedBox(width: 10),
            Text("Konfirmasi Reset", style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          ],
        ),
        content: const Text(
          "Anda yakin ingin mereset ESP32?\n\nSemua sensor akan reboot dan melakukan kalibrasi ulang. Proses ini membutuhkan waktu sekitar 15–30 detik.",
          style: TextStyle(fontSize: 13, color: Colors.grey, height: 1.5),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text("Batal", style: TextStyle(color: Colors.grey)),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
            ),
            child: const Text("Reset ESP32"),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    setState(() => _isResetting = true);
    final success = await _apiService.sendEsp32Reset();
    setState(() => _isResetting = false);

    if (success) {
      _showFeedback("Perintah reset ESP32 berhasil diantrekan. Perangkat akan reboot.");
    } else {
      _showFeedback("Gagal mengirim perintah reset ESP32.", isError: true);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        backgroundColor: const Color(0xFF0F0E0D),
        surfaceTintColor: Colors.transparent,
        elevation: 0,
        title: const Text(
          "RESET ESP32",
          style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 0.8),
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Header
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  "Reset Perangkat",
                  style: TextStyle(fontSize: 24, fontWeight: FontWeight.w900, color: Colors.white),
                ),
                Text(
                  "Kirim perintah restart ke mikrokontroler ESP32.",
                  style: TextStyle(fontSize: 12, color: const Color(0xFFC6B9AE).withOpacity(0.7)),
                ),
              ],
            ),
            const SizedBox(height: 25),

            // ESP32 Info Card
            Card(
              child: Padding(
                padding: const EdgeInsets.all(18.0),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Card Header
                    const Row(
                      children: [
                        Icon(Icons.memory_rounded, color: Color(0xFFC2743E), size: 28),
                        SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                "Mikrokontroler ESP32",
                                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                              ),
                              SizedBox(height: 2),
                              Text(
                                "ESP32 DevKit V1 — Node Sensor SIGMA",
                                style: TextStyle(fontSize: 11, color: Colors.grey),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                    const Divider(height: 30, color: Colors.white10),

                    // Warning block
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.amber.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.amber.withOpacity(0.2)),
                      ),
                      child: const Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Icon(Icons.warning_amber_rounded, color: Colors.amber, size: 20),
                          SizedBox(width: 10),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  "Perhatian",
                                  style: TextStyle(
                                    fontSize: 12,
                                    fontWeight: FontWeight.bold,
                                    color: Colors.amber,
                                  ),
                                ),
                                SizedBox(height: 4),
                                Text(
                                  "Reset akan menghentikan seluruh operasi sensor sementara. ESP32 akan reboot, melakukan kalibrasi ulang, dan kembali mengirim data secara otomatis.",
                                  style: TextStyle(fontSize: 11, color: Colors.grey, height: 1.5),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Info text
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: Colors.white.withOpacity(0.03),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: const Column(
                        children: [
                          _InfoRow(label: "Fungsi", value: "Software Reboot ESP32"),
                          SizedBox(height: 8),
                          _InfoRow(label: "Estimasi waktu", value: "15 – 30 detik"),
                          SizedBox(height: 8),
                          _InfoRow(label: "Mekanisme", value: "Command queue via polling"),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Reset Button
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton.icon(
                        onPressed: _isResetting ? null : _resetEsp32,
                        icon: _isResetting
                            ? const SizedBox(
                                width: 18,
                                height: 18,
                                child: CircularProgressIndicator(
                                  strokeWidth: 2,
                                  color: Colors.white,
                                ),
                              )
                            : const Icon(Icons.restart_alt_rounded, size: 22),
                        label: Text(
                          _isResetting ? "MENGIRIM PERINTAH..." : "RESET ESP32",
                          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, letterSpacing: 0.5),
                        ),
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red,
                          foregroundColor: Colors.white,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                          disabledBackgroundColor: Colors.red.withOpacity(0.5),
                        ),
                      ),
                    ),

                    const SizedBox(height: 10),

                    // Hint
                    const Center(
                      child: Text(
                        "Perintah akan diantrekan dan dijalankan saat ESP32 polling berikutnya.",
                        textAlign: TextAlign.center,
                        style: TextStyle(fontSize: 10, color: Colors.grey),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;

  const _InfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontSize: 11, color: Colors.grey)),
        Text(
          value,
          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFFF1EAE5)),
        ),
      ],
    );
  }
}
