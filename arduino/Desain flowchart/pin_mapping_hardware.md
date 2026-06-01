# Pin Mapping (Konfigurasi Perangkat Keras) — Project SIGMA

Berikut adalah tabel konfigurasi pin antara mikrokontroler ESP32 dengan sensor-sensor yang digunakan pada Project SIGMA.

## Tabel Konfigurasi Pin

| Komponen / Modul | Pin Modul | Pin ESP32 | Keterangan |
|------------------|-----------|-----------|------------|
| **Modul GPS Neo-6M** | VCC | 3V3 / VIN | Catu Daya |
| | GND | GND | Ground |
| | RX | GPIO 19 | Transmit ke GPS (TX ESP32) |
| | TX | GPIO 18 | Receive dari GPS (RX ESP32) |
| **Sensor ADXL345** | VCC | 3V3 | Catu Daya (Akselerometer) |
| | GND | GND | Ground |
| | SDA | GPIO 21 | I2C Data |
| | SCL | GPIO 22 | I2C Clock |
| **OLED Display 128x64** | VCC | 3V3 | Catu Daya (Display) |
| | GND | GND | Ground |
| | SDA | GPIO 21 | I2C Data (Share bus dengan ADXL) |
| | SCL | GPIO 22 | I2C Clock (Share bus dengan ADXL) |
| **Buzzer Aktif** | VCC | GPIO 32 | Output Alarm Peringatan Gempa |
| | GND | GND | Ground |

> **Catatan Penting:** 
> - Modul ADXL345 dan OLED menggunakan komunikasi I2C dan membagikan jalur bus yang sama pada pin GPIO 21 (SDA) dan GPIO 22 (SCL).
> - Buzzer dipasang pada GPIO 32 sebagai Output (HIGH untuk menyala, LOW untuk mati). Sebelumnya buzzer pada GPIO 35 menyebabkan crash karena GPIO 35 adalah Input-Only.
