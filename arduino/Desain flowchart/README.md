# Desain & Diagram — Project SIGMA

Folder ini berisi rancangan arsitektur, diagram alir, dan desain sistem dari pengerjaan Project SIGMA (Seismic Intelligent Ground-motion Monitoring & Alert) yang sangat berguna untuk dimasukkan ke laporan/skripsi Bab 3.

## 📂 Daftar File Diagram

| File | Keterangan |
|------|------------|
| `bagan_metode_kegiatan.html` | **Bagan Metode Kegiatan** — langkah kerja proyek dari awal hingga akhir |
| `blok_diagram_sistem.html` | **Blok Diagram Hardware** — hubungan Input (Sensor) → Proses (ESP32) → Output |
| `arsitektur_sistem.html` | **Arsitektur Jaringan** — topologi aliran data (ESP32 → Wi-Fi → Server → HP) |
| `flowchart_cara_kerja_sistem.html` | **Flowchart Algoritma** — alur logika if/else dari mikrokontroler saat baca sensor |
| `erd_database.html` | **Entity Relationship Diagram (ERD)** — struktur relasi tabel database Laravel |
| `pin_mapping_hardware.md` | **Tabel Wiring** — panduan penyambungan pin ESP32 ke sensor-sensor |
| `flowchart_metode_kegiatan_sigma.png` | Cadangan versi gambar biasa (jangan lupa pakai versi HTML yang lebih HD/rapi) |

## 💡 Cara Pakai (Untuk Laporan)

File berformat `.html` sengaja dirancang agar **mudah di-screenshot** dengan resolusi sangat tinggi dan garis yang tidak akan pernah putus (karena dirender dengan SVG vector).

1. Buka file `.html` (contoh: `erd_database.html`) menggunakan **Browser** (Chrome/Edge/Firefox).
2. Lakukan **Screenshot** atau **Save as PDF** (Ctrl+P -> Save as PDF).
3. Masukkan gambar/PDF tersebut langsung ke dalam dokumen Word / Laporan Anda. Tampilannya sudah dibuat ala standar akademik (Times New Roman, dll).
