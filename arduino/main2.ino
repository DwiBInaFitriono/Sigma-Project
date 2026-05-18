#include <Wire.h>
#include <Adafruit_GFX.h>
#include <Adafruit_SSD1306.h>
#include <Adafruit_Sensor.h>
#include <Adafruit_ADXL345_U.h>
#include <TinyGPSPlus.h>
#include <HardwareSerial.h>
#include <WiFi.h>
#include <HTTPClient.h>

// ---------------------------------------------------------
// HARDWARE CONFIGURATION
// ---------------------------------------------------------
#define SCREEN_WIDTH 128
#define SCREEN_HEIGHT 64
#define OLED_RESET -1

#define BUZZER_PIN 2
#define GPS_RX 16
#define GPS_TX 17

// ---------------------------------------------------------
// NETWORK & API CONFIGURATION
// ---------------------------------------------------------
const char* WIFI_SSID = "Maison";
const char* WIFI_PASSWORD = "SANGATLUXU";
const char* DEVICE_ID = "esp32-sigma-01";
const char* API_BASE_URL = "http://192.168.1.8:8000/api";

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
} state;

// ---------------------------------------------------------
// TUNING PARAMETERS
// ---------------------------------------------------------
const int VIBRATION_DURATION_REQ = 1200;   // ms
const int VIBRATION_RESET_DELAY = 800;     // ms
const float VIBRATION_START_THRESHOLD = 1.5;
const float ALARM_THRESHOLD = 7.8;         // MMI V threshold
const unsigned long UPLOAD_INTERVAL_MS = 2000;         // sinkron dengan dashboard polling
const unsigned long DISPLAY_UPDATE_INTERVAL_MS = 1000;
const unsigned long WIFI_RECONNECT_INTERVAL_MS = 10000;
const int HTTP_TIMEOUT_MS = 3000;          // timeout agar tidak hang

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
void updateDisplayAndSerial(const String& mmiStatus);
void uploadData();
String getStatusMMI(float pga);
bool buildRecordedAt(char* buffer, size_t bufferSize);
bool postJson(const char* url, const char* payload);

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
        while (true) { delay(1000); }
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
        while (true) { delay(1000); }
    }
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

    // Auto-reconnect WiFi jika terputus
    if (now - lastWifiCheck >= WIFI_RECONNECT_INTERVAL_MS) {
        ensureWiFiConnected();
        lastWifiCheck = now;
    }

    // Update OLED + Serial setiap 1 detik
    if (now - lastDisplayUpdate >= DISPLAY_UPDATE_INTERVAL_MS) {
        updateDisplayAndSerial(getStatusMMI(state.smoothedPga));
        lastDisplayUpdate = now;
    }

    // Upload data setiap 2 detik
    if (now - lastUploadMillis >= UPLOAD_INTERVAL_MS) {
        uploadData();
        lastUploadMillis = now;
    }
}

// ---------------------------------------------------------
// WIFI
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

    // Non-blocking: coba 3 detik saja, lalu lanjut loop
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
// CALIBRATION
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
            sumG += sqrt(pow(event.acceleration.x, 2) + pow(event.acceleration.y, 2) + pow(event.acceleration.z, 2));
            validSamples++;
        }
        delay(20);
    }

    if (validSamples > 0) {
        state.baselineG = sumG / (float)validSamples;
    } else {
        state.baselineG = 9.81; // fallback ke 1G standar
        Serial.println(F("WARNING: No valid calibration samples, using default 9.81"));
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
        return;
    }

    // Simpan nilai raw terakhir untuk upload
    state.lastX = event.acceleration.x;
    state.lastY = event.acceleration.y;
    state.lastZ = event.acceleration.z;

    float totalG = sqrt(pow(event.acceleration.x, 2) + pow(event.acceleration.y, 2) + pow(event.acceleration.z, 2));
    float rawPga = abs(totalG - state.baselineG);

    // Low-pass filter
    state.smoothedPga = (0.15 * rawPga) + (0.85 * state.smoothedPga);

    // Deadzone filter
    if (state.smoothedPga < 0.8) {
        state.smoothedPga = 0.0;
    }

    // Vibration detection logic
    if (state.smoothedPga >= VIBRATION_START_THRESHOLD) {
        state.lastVibrationTime = millis();
        if (state.vibrationStartTime == 0) {
            state.vibrationStartTime = millis();
        }

        unsigned long vibrationDuration = millis() - state.vibrationStartTime;

        if (vibrationDuration >= VIBRATION_DURATION_REQ && state.smoothedPga >= ALARM_THRESHOLD) {
            state.isBuzzerActive = true;
            state.buzzerActivationTime = millis();
            digitalWrite(BUZZER_PIN, HIGH);
        }
    }

    if (state.vibrationStartTime > 0 && (millis() - state.lastVibrationTime > VIBRATION_RESET_DELAY)) {
        state.vibrationStartTime = 0;
    }

    // Buzzer auto-off setelah 1 detik
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
    if (pga < 0.34) return "I (Aman)";
    else if (pga < 2.8) return "II-III (Lemah)";
    else if (pga < 7.8) return "IV (Waspada)";
    else if (pga < 18.4) return "V (Bahaya!)";
    else return "VI+ (AWAS!)";
}

// ---------------------------------------------------------
// DISPLAY & SERIAL
// ---------------------------------------------------------
void updateDisplayAndSerial(const String& mmiStatus) {
    display.clearDisplay();
    display.setCursor(0, 0);
    display.println(F("SIGMA Monitor"));
    display.print(F("PGA: ")); display.print(state.smoothedPga, 2); display.println(F(" m/s2"));
    display.print(F("MMI: ")); display.println(mmiStatus);

    if (gps.location.isValid()) {
        display.print(F("Lat: ")); display.println(gps.location.lat(), 4);
    } else {
        display.println(F("GPS: Searching..."));
    }

    // Status WiFi di OLED
    display.print(F("WiFi: "));
    display.println(WiFi.status() == WL_CONNECTED ? F("OK") : F("--"));
    display.display();

    Serial.println(F("============================"));
    Serial.print(F("PGA (Filtered): ")); Serial.print(state.smoothedPga, 2); Serial.println(F(" m/s2"));
    Serial.print(F("MMI Status    : ")); Serial.println(mmiStatus);
    Serial.print(F("Accel X/Y/Z   : ")); Serial.print(state.lastX, 2); Serial.print(F(" / ")); Serial.print(state.lastY, 2); Serial.print(F(" / ")); Serial.println(state.lastZ, 2);

    if (state.vibrationStartTime > 0) {
        Serial.print(F("Vib. Duration : ")); Serial.print(millis() - state.vibrationStartTime); Serial.println(F(" ms"));
    }

    if (gps.location.isValid()) {
        Serial.print(F("GPS Location  : Lat ")); Serial.print(gps.location.lat(), 6);
        Serial.print(F(", Lng ")); Serial.println(gps.location.lng(), 6);
    } else {
        Serial.println(F("GPS Location  : Searching for satellites..."));
    }

    Serial.print(F("ALARM         : ")); Serial.println(state.isBuzzerActive ? F("ACTIVE !!!") : F("Standby"));
    Serial.print(F("WiFi          : ")); Serial.println(WiFi.status() == WL_CONNECTED ? F("Connected") : F("Disconnected"));
    Serial.println(F("============================\n"));
}

// ---------------------------------------------------------
// NETWORK UPLOAD
// ---------------------------------------------------------
bool buildRecordedAt(char* buffer, size_t bufferSize) {
    if (gps.date.isValid() && gps.time.isValid()) {
        snprintf(buffer, bufferSize, "%04d-%02d-%02dT%02d:%02d:%02dZ",
            gps.date.year(), gps.date.month(), gps.date.day(),
            gps.time.hour(), gps.time.minute(), gps.time.second());
        return true;
    }
    return false;
}

bool postJson(const char* url, const char* payload) {
    if (WiFi.status() != WL_CONNECTED) {
        return false;
    }

    WiFiClient client;
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
    // Buffer untuk JSON payload (menggunakan stack, bukan heap String)
    char payload[384];
    char recordedAt[25];
    bool hasTime = buildRecordedAt(recordedAt, sizeof(recordedAt));

    // Endpoint URLs (buat sekali saja di stack)
    char urlGps[128];
    char urlAccel[128];
    snprintf(urlGps, sizeof(urlGps), "%s/sensors/gps", API_BASE_URL);
    snprintf(urlAccel, sizeof(urlAccel), "%s/sensors/accelerometer", API_BASE_URL);

    // GPS Upload
    if (gps.location.isValid()) {
        if (hasTime) {
            snprintf(payload, sizeof(payload),
                "{\"device_id\":\"%s\",\"latitude\":%.7f,\"longitude\":%.7f,\"altitude\":%.2f,\"satellites\":%d,\"status\":\"3D FIX\",\"recorded_at\":\"%s\"}",
                DEVICE_ID,
                gps.location.lat(), gps.location.lng(),
                gps.altitude.isValid() ? gps.altitude.meters() : 0.0,
                gps.satellites.isValid() ? gps.satellites.value() : 0,
                recordedAt);
        } else {
            snprintf(payload, sizeof(payload),
                "{\"device_id\":\"%s\",\"latitude\":%.7f,\"longitude\":%.7f,\"altitude\":%.2f,\"satellites\":%d,\"status\":\"3D FIX\",\"recorded_at\":null}",
                DEVICE_ID,
                gps.location.lat(), gps.location.lng(),
                gps.altitude.isValid() ? gps.altitude.meters() : 0.0,
                gps.satellites.isValid() ? gps.satellites.value() : 0);
        }
        postJson(urlGps, payload);
    }

    // Accelerometer Upload
    if (hasTime) {
        snprintf(payload, sizeof(payload),
            "{\"device_id\":\"%s\",\"x\":%.4f,\"y\":%.4f,\"z\":%.4f,\"magnitude\":%.4f,\"recorded_at\":\"%s\"}",
            DEVICE_ID, state.lastX, state.lastY, state.lastZ, state.smoothedPga, recordedAt);
    } else {
        snprintf(payload, sizeof(payload),
            "{\"device_id\":\"%s\",\"x\":%.4f,\"y\":%.4f,\"z\":%.4f,\"magnitude\":%.4f,\"recorded_at\":null}",
            DEVICE_ID, state.lastX, state.lastY, state.lastZ, state.smoothedPga);
    }
    postJson(urlAccel, payload);
}