#include <ESP8266WiFi.h>
#include <ESP8266HTTPClient.h>
#include <WiFiClientSecure.h>
#include <TinyGPSPlus.h>
#include <SoftwareSerial.h>

// --- PROSEDUR KONFIGURASI WIFI ---
const char* ssid = "RIO";
const char* password = "riomia454706";

// --- PROSEDUR KONFIGURASI API SIGMA ---
// Menghubungkan langsung ke endpoint /sensors/gps sesuai API Laravel SIGMA saat ini
const char* serverName = "https://sigma.sfht.space/api/sensors/gps";

// --- PROSEDUR KONFIGURASI DEVICE ID ---
const char* deviceId = "esp32-sigma-01"; // ID Default agar data masuk ke dashboard monitor utama

// --- PROSEDUR KONFIGURASI PIN WEMOS ---
static const int RXPin = D1; // Dihubungkan ke pin TX GPS
static const int TXPin = D2; // Dihubungkan ke pin RX GPS
static const uint32_t GPSBaud = 9600;

TinyGPSPlus gps;
SoftwareSerial gpsSerial(RXPin, TXPin);

unsigned long lastTime = 0;
unsigned long timerDelay = 10000; // Interval kirim data (10.000 ms = 10 detik)

void setup() {
  Serial.begin(9600);
  gpsSerial.begin(GPSBaud);

  // Prosedur Jeda: Memberikan waktu agar Serial Monitor stabil dan tidak memunculkan karakter kotak-kokak
  delay(1000); 
  
  Serial.println(F("\n\n========================================="));
  Serial.println(F("       INISIALISASI SISTEM SIGMA         "));
  Serial.println(F("========================================="));

  // Prosedur 1: Membangun Koneksi Jaringan
  Serial.print(F("Menghubungkan ke WiFi ["));
  Serial.print(ssid);
  Serial.print(F("] "));
  
  WiFi.begin(ssid, password);
  while(WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(F(".")); // Menampilkan titik-titik selama proses koneksi berlangsung
  }
  
  Serial.println(F("\n[SUKSES] WiFi Terhubung!"));
  Serial.println(F("Prosedur 2: Membaca Data Satelit GPS... (Harus di luar ruangan)"));
}

void loop() {
  // Prosedur 3: Looping Pembacaan Sensor GPS secara terus menerus
  while (gpsSerial.available() > 0) {
    gps.encode(gpsSerial.read());
  }

  // Prosedur 4: Eksekusi Pengiriman Payload JSON ke Server
  if (millis() - lastTime > timerDelay) {
    
    // Validasi 1: Pastikan alat masih tersambung ke WiFi
    if(WiFi.status() == WL_CONNECTED){
      
      Serial.println(F("\n[INFO] Menyiapkan pengiriman data ke server..."));
      
      WiFiClientSecure client;
      client.setInsecure(); // Melewati verifikasi SSL sertifikat
      HTTPClient http;

      http.begin(client, serverName);
      http.addHeader("Content-Type", "application/json");

      String latStr, lngStr, altStr, satStr, statusStr;
      
      if (gps.location.isValid()) {
        latStr = String(gps.location.lat(), 6);
        lngStr = String(gps.location.lng(), 6);
        altStr = String(gps.altitude.isValid() ? gps.altitude.meters() : 0.0, 2);
        satStr = String(gps.satellites.isValid() ? gps.satellites.value() : 0);
        statusStr = "3D FIX";
      } else {
        // Kirim data default (0.0) saat GPS belum mengunci posisi (NO FIX)
        // Ini bertindak sebagai Heartbeat agar status perangkat di web terbaca 'TERHUBUNG'
        latStr = "0.000000";
        lngStr = "0.000000";
        altStr = "0.00";
        satStr = String(gps.satellites.isValid() ? gps.satellites.value() : 0);
        statusStr = "NO FIX";
      }

      // Format data JSON untuk database SIGMA
      String httpRequestData = "{"
        "\"device_id\":\"" + String(deviceId) + "\","
        "\"latitude\":" + latStr + ","
        "\"longitude\":" + lngStr + ","
        "\"altitude\":" + altStr + ","
        "\"satellites\":" + satStr + ","
        "\"status\":\"" + statusStr + "\""
      "}";
      
      Serial.print(F("Payload : "));
      Serial.println(httpRequestData);

      // Eksekusi POST
      int httpResponseCode = http.POST(httpRequestData);
      
      // Evaluasi Respon dari API
      if (httpResponseCode > 0) {
        Serial.print(F("[SUKSES] Kode Respon Server : "));
        Serial.println(httpResponseCode); // Sukses jika merespon 200 atau 201
        
        String payload = http.getString();
        Serial.println("Respon: " + payload);
      } else {
        Serial.print(F("[GAGAL] Error pengiriman kode : "));
        Serial.println(httpResponseCode);
      }
      
      http.end();
      
      if (!gps.location.isValid()) {
        Serial.println(F("[MENUNGGU] GPS belum mendapatkan sinyal satelit. Cari tempat terbuka di luar ruangan."));
      }
    } else {
      Serial.println(F("[ERROR] WiFi Terputus!"));
    }
    
    lastTime = millis();
  }
}
