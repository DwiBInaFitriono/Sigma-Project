#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_ADXL345_U.h>
#include <TinyGPSPlus.h>
#include <HardwareSerial.h>
#include <WiFi.h>
#include <WiFiClientSecure.h>
#include <HTTPClient.h>

// ---------------------------------------------------------
// HARDWARE CONFIGURATION
// ---------------------------------------------------------
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET -1

// [DISESUAIKAN] Buzzer dipindah ke GPIO 32 karena GPIO 35 hanya bisa untuk input (bikin crash)
#define BUZZER_PIN 35

// Jalur GPS (RX=18, TX=19)
#define GPS_RX 19
#define GPS_TX 18

// ---------------------------------------------------------
// NETWORK & API CONFIGURATION
// ---------------------------------------------------------
const char *WIFI_SSID = "Infinix HOT 11 Play";
const char *WIFI_PASSWORD = "anjaynumpangyak";
const char *DEVICE_ID = "esp32-sigma-01";
const char *API_BASE_URL = "https://sigma-project-one.vercel.app/api";

// ---------------------------------------------------------
// GLOBAL OBJECTS
// ---------------------------------------------------------
Adafruit_SSD1306 display(SCREEN_WIDTH, SCREEN_HEIGHT, &Wire, OLED_RESET);
Adafruit_ADXL345_Unified accel = Adafruit_ADXL345_Unified(12345);
TinyGPSPlus gps;
HardwareSerial gpsSerial(2);

// ---------------------------------------------------------
// SENSOR STATE
// ---------------------------------------------------------
struct SensorState {
  float baselineG = 0.0;
  float smoothedPga = 0.0;
  float lastX = 0.0;
  float lastY = 0.0;
  float lastZ = 0.0;
  unsigned long vibrationStartTime = 0;
  unsigned long lastVibrationTime = 0;
  bool isBuzzerActive = false;
  unsigned long buzzerActivationTime = 0;
  unsigned long lastProcessTime = 0; // Timer pelacak waktu
} state;

// ---------------------------------------------------------
// TUNING PARAMETERS
// ---------------------------------------------------------
const int VIBRATION_DURATION_REQ = 1200;   // ms 
const int VIBRATION_RESET_DELAY = 800;     // ms
const float VIBRATION_START_THRESHOLD = 1.5; // m/s2 (Manual shake threshold)
const float ALARM_THRESHOLD = 7.8;         // m/s2 (Batas buzzer goyangan)
const unsigned long UPLOAD_INTERVAL_MS = 2000;
const unsigned long DISPLAY_UPDATE_INTERVAL_MS = 1000;
const unsigned long WIFI_RECONNECT_INTERVAL_MS = 10000;
const int HTTP_TIMEOUT_MS = 3000;

// ---------------------------------------------------------
// TIMING STATE
// ---------------------------------------------------------
unsigned long lastUploadMillis = 0;
unsigned long lastDisplayUpdate = 0;
unsigned long lastWifiCheck = 0;

// ---------------------------------------------------------
// FUNCTION PROTOTYPES
// ---------------------------------------------------------
void connectToWiFi();
void ensureWiFiConnected();
void calibrateSensor();
void processGPS();
void processAccelerometer();
void updateDisplayAndSerial(const String &mmiStatus);
void uploadData();
String getStatusMMI(float pga);
bool buildRecordedAt(char *buffer, size_t bufferSize);
bool postJson(const char *url, const char *payload);

// ---------------------------------------------------------
// SETUP
// ---------------------------------------------------------
void setup() {
  Serial.begin(115200);
  Serial.println(F("\n=== SIGMA Earthquake Detector System ==="));

  pinMode(BUZZER_PIN, OUTPUT);
  digitalWrite(BUZZER_PIN, LOW);

  gpsSerial.begin(9600, SERIAL_8N1, GPS_RX, GPS_TX);

  if (!display.begin(SSD1306_SWITCHCAPVCC, 0x3C)) {
    Serial.println(F("ERROR: OLED init failed"));
    while (true) {
      delay(1000);
    }
  }
  display.clearDisplay();
  display.setTextSize(1);
  display.setTextColor(WHITE);
  display.setCursor(0, 10);
  display.println(F("SIGMA Booting..."));
  display.display();

  connectToWiFi();

  if (!accel.begin()) {
    Serial.println(F("ERROR: ADXL345 init failed"));
    display.clearDisplay();
    display.setCursor(0, 10);
    display.println(F("ADXL345 Error!"));
    display.display();
    while (true) {
      delay(1000);
    }
  }
  
  // Anti-Hang (Mencegah sistem freeze jika kabel sensor goyang saat dinamo nyala)
  Wire.setTimeOut(150);
  
  accel.setRange(ADXL345_RANGE_2_G);

  calibrateSensor();

  Serial.println(F("=== SYSTEM STANDBY ==="));
  display.clearDisplay();
  display.setCursor(0, 10);
  display.println(F("System Ready"));
  display.display();
  delay(1000);
}

// ---------------------------------------------------------
// MAIN LOOP
// ---------------------------------------------------------
void loop() {
  unsigned long now = millis();

  processGPS();
  processAccelerometer();

  if (now - lastWifiCheck >= WIFI_RECONNECT_INTERVAL_MS) {
    ensureWiFiConnected();
    lastWifiCheck = now;
  }

  if (now - lastDisplayUpdate >= DISPLAY_UPDATE_INTERVAL_MS) {
    updateDisplayAndSerial(getStatusMMI(state.smoothedPga));
    lastDisplayUpdate = now;
  }

  if (now - lastUploadMillis >= UPLOAD_INTERVAL_MS) {
    uploadData();
    lastUploadMillis = now;
  }
}

// ---------------------------------------------------------
// WIFI MANAGEMENT
// ---------------------------------------------------------
void connectToWiFi() {
  WiFi.mode(WIFI_STA);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  Serial.print(F("Connecting to WiFi"));
  display.clearDisplay();
  display.setCursor(0, 10);
  display.println(F("Connecting WiFi..."));
  display.display();

  int attempts = 0;
  while (WiFi.status() != WL_CONNECTED && attempts < 30) {
    delay(500);
    Serial.print(F("."));
    attempts++;
  }
  Serial.println();

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print(F("OK: WiFi Connected -> "));
    Serial.println(WiFi.localIP());
  } else {
    Serial.println(F("WARNING: WiFi Connection Failed."));
  }
}

void ensureWiFiConnected() {
  if (WiFi.status() == WL_CONNECTED) {
    return;
  }

  Serial.println(F("WiFi disconnected. Reconnecting..."));
  WiFi.disconnect();
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  unsigned long start = millis();
  while (WiFi.status() != WL_CONNECTED && (millis() - start) < 3000) {
    delay(100);
  }

  if (WiFi.status() == WL_CONNECTED) {
    Serial.print(F("WiFi reconnected: "));
    Serial.println(WiFi.localIP());
  } else {
    Serial.println(F("WiFi reconnect failed. Will retry later."));
  }
}

// ---------------------------------------------------------
// SENSOR CALIBRATION
// ---------------------------------------------------------
void calibrateSensor() {
  Serial.println(F("Starting calibration..."));
  for (int i = 5; i > 0; i--) {
    display.clearDisplay();
    display.setCursor(0, 10);
    display.println(F("Calibration Mode"));
    display.print(F("Time remaining: "));
    display.print(i);
    display.println(F("s"));
    display.println(F("DO NOT MOVE!"));
    display.display();
    delay(1000);
  }

  display.clearDisplay();
  display.setCursor(0, 10);
  display.println(F("Recording Baseline..."));
  display.display();

  float sumG = 0;
  int validSamples = 0;
  for (int i = 0; i < 100; i++) {
    sensors_event_t event;
    if (accel.getEvent(&event)) {
      sumG += sqrt(pow(event.acceleration.x, 2) + pow(event.acceleration.y, 2) +
                   pow(event.acceleration.z, 2));
      validSamples++;
    }
    delay(20);
  }

  if (validSamples > 0) {
    state.baselineG = sumG / (float)validSamples;
  } else {
    state.baselineG = 9.81;
    Serial.println(
        F("WARNING: No valid calibration samples, using default 9.81"));
  }

  Serial.print(F("Baseline Gravity: "));
  Serial.println(state.baselineG);
}

// ---------------------------------------------------------
// SENSOR PROCESSING
// ---------------------------------------------------------
void processGPS() {
  while (gpsSerial.available() > 0) {
    gps.encode(gpsSerial.read());
  }
}

void processAccelerometer() {
  sensors_event_t event;
  if (!accel.getEvent(&event)) {
    // Jika kabel goyang/kendur dan gagal baca, paksa nilai ke 0 agar buzzer tidak nyangkut
    state.smoothedPga = 0.0;
    state.isBuzzerActive = false;
    digitalWrite(BUZZER_PIN, LOW);
    return;
  }

  state.lastX = event.acceleration.x;
  state.lastY = event.acceleration.y;
  state.lastZ = event.acceleration.z;

  float totalG =
      sqrt(pow(event.acceleration.x, 2) + pow(event.acceleration.y, 2) +
           pow(event.acceleration.z, 2));

  // 1. Auto-kalibrasi PINTAR (Hanya saat sedang diam / getaran kecil)
  // Ini mencegah baseline rusak/bergeser ke atas saat dinamo menyala kencang!
  if (abs(totalG - state.baselineG) < 1.0) {
      state.baselineG = (0.90 * state.baselineG) + (0.10 * totalG);
  }

  float rawPga = abs(totalG - state.baselineG);

  // Abaikan noise sangat kecil agar tidak merusak perhitungan
  if (rawPga < 0.15) {
      rawPga = 0.0;
  }

  // 2. Linear Time-Based Decay (Turun instan)
  unsigned long now = millis();
  unsigned long dt = now - state.lastProcessTime;
  if (state.lastProcessTime == 0) dt = 0; 
  state.lastProcessTime = now;

  if (dt > 0 && dt < 5000) {
    // Sapu bersih! Anjlok 15.0 m/s2 setiap detiknya.
    // Misal angka nyangkut di 4.5, ia akan jatuh ke 0.0 mutlak dalam waktu 0.3 detik!
    float dropAmount = (dt / 1000.0) * 15.0;
    state.smoothedPga -= dropAmount;
    if (state.smoothedPga < 0) {
      state.smoothedPga = 0.0;
    }
  }

  // 3. Peak Hold (Hanya naik saat dinamo menyala)
  if (rawPga > state.smoothedPga) {
    state.smoothedPga = rawPga;
  }

  if (state.smoothedPga >= VIBRATION_START_THRESHOLD) {
    state.lastVibrationTime = millis();
    if (state.vibrationStartTime == 0) {
      state.vibrationStartTime = millis();
    }

    unsigned long vibrationDuration = millis() - state.vibrationStartTime;

    if (vibrationDuration >= VIBRATION_DURATION_REQ &&
        state.smoothedPga >= ALARM_THRESHOLD) {
      state.isBuzzerActive = true;
      state.buzzerActivationTime = millis();
      digitalWrite(BUZZER_PIN, HIGH);
    }
  }

  if (state.vibrationStartTime > 0 &&
      (millis() - state.lastVibrationTime > VIBRATION_RESET_DELAY)) {
    state.vibrationStartTime = 0;
  }

  if (state.isBuzzerActive) {
    if (millis() - state.buzzerActivationTime >= 1000) {
      state.isBuzzerActive = false;
      digitalWrite(BUZZER_PIN, LOW);
    }
  } else {
    digitalWrite(BUZZER_PIN, LOW);
  }
}

String getStatusMMI(float pga) {
  if (pga < 0.34)
    return "I (Aman)";
  else if (pga < 2.8)
    return "II-III (Lemah)";
  else if (pga < 7.8)
    return "IV (Waspada)";
  else if (pga < 18.4)
    return "V (Bahaya!)";
  else
    return "VI+ (AWAS!)";
}

// ---------------------------------------------------------
// DISPLAY & SERIAL OUTPUT
// ---------------------------------------------------------
void updateDisplayAndSerial(const String &mmiStatus) {
  display.clearDisplay();
  display.setCursor(0, 0);
  display.println(F("SIGMA Monitor"));
  display.print(F("PGA: "));
  display.print(state.smoothedPga, 2);
  display.println(F(" m/s2"));
  display.print(F("MMI: "));
  display.println(mmiStatus);

  if (gps.location.isValid()) {
    display.print(F("Lat: "));
    display.println(gps.location.lat(), 4);
  } else {
    display.println(F("GPS: Searching..."));
  }

  display.print(F("WiFi: "));
  display.println(WiFi.status() == WL_CONNECTED ? F("OK") : F("--"));
  display.display();

  Serial.println(F("============================"));
  Serial.print(F("PGA (Filtered): "));
  Serial.print(state.smoothedPga, 2);
  Serial.println(F(" m/s2"));
  Serial.print(F("MMI Status    : "));
  Serial.println(mmiStatus);
  Serial.print(F("Accel X/Y/Z   : "));
  Serial.print(state.lastX, 2);
  Serial.print(F(" / "));
  Serial.print(state.lastY, 2);
  Serial.print(F(" / "));
  Serial.println(state.lastZ, 2);

  if (state.vibrationStartTime > 0) {
    Serial.print(F("Vib. Duration : "));
    Serial.print(millis() - state.vibrationStartTime);
    Serial.println(F(" ms"));
  }

  if (gps.location.isValid()) {
    Serial.print(F("GPS Location  : Lat "));
    Serial.print(gps.location.lat(), 6);
    Serial.print(F(", Lng "));
    Serial.println(gps.location.lng(), 6);
  } else {
    Serial.println(F("GPS Location  : Searching for satellites..."));
  }

  Serial.print(F("ALARM         : "));
  Serial.println(state.isBuzzerActive ? F("ACTIVE !!!") : F("Standby"));
  Serial.print(F("WiFi          : "));
  Serial.println(WiFi.status() == WL_CONNECTED ? F("Connected")
                                               : F("Disconnected"));
  Serial.println(F("============================\n"));
}

// ---------------------------------------------------------
// NETWORK DATA UPLOAD
// ---------------------------------------------------------
bool buildRecordedAt(char *buffer, size_t bufferSize) {
  if (gps.date.isValid() && gps.time.isValid()) {
    snprintf(buffer, bufferSize, "%04d-%02d-%02dT%02d:%02d:%02dZ",
             gps.date.year(), gps.date.month(), gps.date.day(), gps.time.hour(),
             gps.time.minute(), gps.time.second());
    return true;
  }
  return false;
}

bool postJson(const char *url, const char *payload) {
  if (WiFi.status() != WL_CONNECTED) {
    return false;
  }

  WiFiClientSecure client;
  client.setInsecure();
  client.setTimeout(3); // Mencegah freeze saat HTTPS Handshake (koneksi wifi lambat)
  
  HTTPClient http;
  http.begin(client, url);
  http.addHeader("Content-Type", "application/json");
  http.setTimeout(HTTP_TIMEOUT_MS);

  int httpCode = http.POST(payload);
  bool success = (httpCode >= 200 && httpCode < 300);

  if (!success) {
    Serial.print(F("[HTTP] POST failed -> "));
    Serial.print(url);
    Serial.print(F(" code="));
    Serial.println(httpCode);
  }

  http.end();
  return success;
}

void uploadData() {
  char payload[384];
  char recordedAt[25];
  bool hasTime = buildRecordedAt(recordedAt, sizeof(recordedAt));

  char urlGps[128];
  char urlAccel[128];
  snprintf(urlGps, sizeof(urlGps), "%s/sensors/gps", API_BASE_URL);
  snprintf(urlAccel, sizeof(urlAccel), "%s/sensors/accelerometer",
           API_BASE_URL);

  // GPS Data Upload
  // Tetap kirim data GPS jika modul aktif (charsProcessed > 0) agar dashboard menampilkan status "Online"
  if (gps.charsProcessed() > 0) {
    if (gps.location.isValid()) {
      if (hasTime) {
        snprintf(payload, sizeof(payload),
                 "{\"device_id\":\"%s\",\"latitude\":%.7f,\"longitude\":%.7f,"
                 "\"altitude\":%.2f,\"satellites\":%d,\"status\":\"3D "
                 "FIX\",\"recorded_at\":\"%s\"}",
                 DEVICE_ID, gps.location.lat(), gps.location.lng(),
                 gps.altitude.isValid() ? gps.altitude.meters() : 0.0,
                 gps.satellites.isValid() ? gps.satellites.value() : 0,
                 recordedAt);
      } else {
        snprintf(payload, sizeof(payload),
                 "{\"device_id\":\"%s\",\"latitude\":%.7f,\"longitude\":%.7f,"
                 "\"altitude\":%.2f,\"satellites\":%d,\"status\":\"3D "
                 "FIX\",\"recorded_at\":null}",
                 DEVICE_ID, gps.location.lat(), gps.location.lng(),
                 gps.altitude.isValid() ? gps.altitude.meters() : 0.0,
                 gps.satellites.isValid() ? gps.satellites.value() : 0);
      }
    } else {
      // Jika belum lock satelit (NO FIX), kirim koordinat default agar backend mencatat status online GPS
      snprintf(payload, sizeof(payload),
               "{\"device_id\":\"%s\",\"latitude\":0.0,\"longitude\":0.0,"
               "\"altitude\":0.0,\"satellites\":%d,\"status\":\"NO "
               "FIX\",\"recorded_at\":null}",
               DEVICE_ID, gps.satellites.isValid() ? gps.satellites.value() : 0);
    }
    postJson(urlGps, payload);
  }

  // Accelerometer Data Upload
  if (hasTime) {
    snprintf(payload, sizeof(payload),
             "{\"device_id\":\"%s\",\"x\":%.4f,\"y\":%.4f,\"z\":%.4f,"
             "\"magnitude\":%.4f,\"recorded_at\":\"%s\"}",
             DEVICE_ID, state.lastX, state.lastY, state.lastZ,
             state.smoothedPga, recordedAt);
  } else {
    snprintf(payload, sizeof(payload),
             "{\"device_id\":\"%s\",\"x\":%.4f,\"y\":%.4f,\"z\":%.4f,"
             "\"magnitude\":%.4f,\"recorded_at\":null}",
             DEVICE_ID, state.lastX, state.lastY, state.lastZ,
             state.smoothedPga);
  }
  postJson(urlAccel, payload);
}