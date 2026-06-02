# Panduan Perhitungan Sensor & Deteksi Gempa Bumi (SIGMA)

Dokumen ini menjelaskan bagaimana mikrokontroler ESP32 membaca data dari sensor akselerometer ADXL345, melakukan perhitungan fisika untuk menyaring getaran dinamo/kebisingan, serta mengklasifikasikan kekuatan getaran tersebut menjadi skala Modified Mercalli Intensity (MMI) dan mengaktifkan alarm gempa.

Seluruh logika perhitungan ini diimplementasikan langsung pada file program utama **[main.ino](file:///d:/Project%20Laravel/Sigma-Project/arduino/main.ino)**.

---

## 1. Pembacaan Nilai Akselerasi Mentah (X, Y, Z)
Sensor ADXL345 mendeteksi akselerasi dinamis (akibat gerakan/getaran) dan akselerasi statis (akibat gaya gravitasi bumi) pada tiga sumbu spasial:
* **Sumbu X**: Gerakan horizontal ke kanan/kiri.
* **Sumbu Y**: Gerakan horizontal ke depan/belakang.
* **Sumbu Z**: Gerakan vertikal ke atas/bawah.

Nilai dibaca dalam satuan meter per sekon kuadrat ($m/s^2$) menggunakan pustaka Adafruit Unified Sensor.

---

## 2. Penghitungan Magnitudo Total (Euclidean Norm)
Untuk mendapatkan total gaya akselerasi yang dialami sensor tanpa memedulikan arah orientasi perangkat, kita menggunakan rumus jarak Euclidean (norma vektor 3 dimensi):

$$a_{total} = \sqrt{a_x^2 + a_y^2 + a_z^2}$$

Di mana:
* $a_x, a_y, a_z$ adalah nilai akselerasi yang dibaca oleh sensor.
* $a_{total}$ adalah magnitudo total akselerasi dalam satuan $m/s^2$.

---

## 3. Kalibrasi Awal & Pelacakan Baseline Gravitasi
Saat pertama kali menyala, sensor akan merekam gravitasi bumi statis (baseline) saat perangkat diam sempurna melalui metode kalibrasi rata-rata (Calibration Mode):

1. **Kalibrasi Awal (Setup)**:
   Mikrokontroler mengambil 100 sampel akselerasi total dan menghitung rata-ratanya:
   $$g_{baseline} = \frac{1}{100} \sum_{i=1}^{100} a_{total, i}$$
   Nilai ini disimpan dalam variabel `state.baselineG` (berkisar sekitar $9.81\ m/s^2$).

2. **Auto-Kalibrasi Pintar (Running)**:
   Untuk mengantisipasi adanya pergeseran sudut kemiringan (tilt) atau perubahan suhu, baseline diperbarui secara perlahan menggunakan filter linier dinamis saat sensor berada dalam kondisi diam atau getaran sangat kecil ($|a_{total} - g_{baseline}| < 1.0$):
   $$g_{baseline} = (0.90 \times g_{baseline}) + (0.10 \times a_{total})$$

---

## 4. Penghitungan Peak Ground Acceleration (PGA)
Gaya gempa bumi murni dihitung dari selisih mutlak antara akselerasi saat ini terhadap baseline gravitasi statis bumi:

$$PGA_{raw} = |a_{total} - g_{baseline}|$$

### Penyaringan Kebisingan & Efek Luruh (Decay)
* **Deadzone Filter**: Untuk menyaring noise getaran sangat kecil dari sensor, nilai diabaikan jika di bawah batas:
  $$\text{Jika } PGA_{raw} < 0.15\ m/s^2, \text{ maka } PGA_{raw} = 0.0$$
* **Peak Hold & Linear Decay**: Untuk membuat grafik di web/app terlihat mulus dan tidak langsung anjlok secara drastis saat terjadi getaran sekilas, nilai puncak dipertahankan dengan penurunan perlahan sebesar $15.0\ m/s^2$ per detik:
  $$PGA_{smoothed} = PGA_{smoothed} - (\Delta t \times 15.0)$$
  Jika getaran baru lebih besar dari nilai luruh, maka nilai diperbarui ke puncak tertinggi baru:
  $$\text{Jika } PGA_{raw} > PGA_{smoothed}, \text{ maka } PGA_{smoothed} = PGA_{raw}$$

---

## 5. Klasifikasi Kekuatan Gempa (Skala MMI)
Nilai $PGA_{smoothed}$ yang telah difilter kemudian diklasifikasikan ke dalam skala Modified Mercalli Intensity (MMI) berdasarkan tingkat dampak getarannya bagi lingkungan sekitar:

| Batas PGA ($m/s^2$) | Skala MMI | Status | Deskripsi Sensasi |
| :--- | :--- | :--- | :--- |
| $< 0.34$ | **I** | **Aman** | Tidak terasa, hanya terekam oleh instrumen. |
| $0.34 \le \text{PGA} < 2.80$ | **II - III** | **Lemah** | Terasa oleh beberapa orang yang diam di dalam ruangan. |
| $2.80 \le \text{PGA} < 7.80$ | **IV** | **Waspada** | Terasa oleh banyak orang; pintu/jendela berderit. |
| $7.80 \le \text{PGA} < 18.40$ | **V** | **Bahaya!** | Tiang/barang bergoyang; barang pecah belah jatuh. |
| $\ge 18.40$ | **VI+** | **AWAS!** | Kerusakan ringan pada bangunan; kepanikan massal. |

Logika klasifikasi ini dieksekusi di fungsi `getStatusMMI()` pada file **[main.ino](file:///d:/Project%20Laravel/Sigma-Project/arduino/main.ino#L358-L369)**.

---

## 6. Logika Pemicu Alarm Gempa (Buzzer)
Agar alarm buzzer tidak berbunyi hanya karena senggolan kecil tak sengaja (false alarm), mikrokontroler menggunakan akumulator durasi waktu:

1. **Deteksi Getaran Aktif**: Getaran dideteksi dimulai saat $PGA_{smoothed} \ge 1.5\ m/s^2$ (Variabel `VIBRATION_START_THRESHOLD`). Waktu awal dicatat ke `vibrationStartTime`.
2. **Kondisi Alarm Menyala**: Alarm berbunyi jika:
   * Durasi getaran terus-menerus $\ge 1200\text{ ms}$ (Variabel `VIBRATION_DURATION_REQ`).
   * **DAN** Kekuatan getaran mencapai $\ge 7.8\ m/s^2$ (Variabel `ALARM_THRESHOLD` setara MMI V).
3. **Buzzer Mati Otomatis**: Jika kondisi di atas terpenuhi, pin `BUZZER_PIN` diset `HIGH` selama $1000\text{ ms}$, lalu akan otomatis mati kembali.
