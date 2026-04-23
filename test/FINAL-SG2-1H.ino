// ============================================================
//  SENSOR TELEMETRY — ESP32 + DHT11 + LDR + ACS712 + Voltage
//  Firebase: /SG-NODE2/{Sensor, Actuator, Health, Predictive, Control}
//  With predictive maintenance & smart fault detection
// ============================================================

#include <ArduinoJson.h>
#include <DHT.h>
#include <HTTPClient.h>
#include <WiFi.h>
// ── Health evaluation result ─────────────────────────────────
struct HealthStatus {
  String envTempStatus;
  String envHumidityStatus;
  String lampStatus;
  String dhtStatus;
  bool   warningFlag;
};

// ── Predictive maintenance result ────────────────────────────
struct PredictiveStatus {
  String lampHealth;       // IDLE / GOOD / DEGRADING / REPLACE
  float  avgCurrent;
  float  expectedCurrent;
  String powerStability;   // STABLE / UNSTABLE
  float  voltageAvg;
  float  operatingHours;
  String maintenanceAlert; // descriptive string
};

// ── WiFi Credentials ─────────────────────────────────────────
#define WIFI_SSID "ekard"
#define WIFI_PASSWORD "12345678"

// ── Firebase ──────────────────────────────────────────────────
#define FIREBASE_HOST                                                          \
  "https://shineguardhulo-1h-default-rtdb.asia-southeast1.firebasedatabase.app"
#define NODE_PATH "/SG-NODE2.json"
#define CONTROL_PATH "/SG-NODE2/Control.json"

// ── Pin Definitions ──────────────────────────────────────────
#define DHT_PIN 4
#define DHT_TYPE DHT11

#define LDR_AO_PIN 34
#define LDR_DO_PIN 32
#define VOLT_PIN 33
#define ACS_PIN 35
#define LED_PIN 25

// ── PWM Config ───────────────────────────────────────────────
#define PWM_FREQ 5000
#define PWM_RESOLUTION 8

// ── Calibration ──────────────────────────────────────────────
const float VOLT_MULTIPLIER = 7.5;
const float CURRENT_SENSITIVITY = 0.185;
const float ACS_ZERO_OFFSET = 2.5;

// ── Auto mode ────────────────────────────────────────────────
const int AUTO_NIGHT_PWM = 255;

// ── Globals ───────────────────────────────────────────────────
DHT dht(DHT_PIN, DHT_TYPE);

int currentPWM = 0;
bool autoMode = false;

// ── Health: consecutive fault counters ───────────────────────
// When a fault persists for FAULT_THRESHOLD consecutive reads → WARNING
const int FAULT_THRESHOLD = 10;

int consecutiveLampFaults = 0;  // LED on but no current
int consecutiveTempFaults = 0;  // temp above safe limit
int consecutiveHumFaults  = 0;  // humidity above safe limit
int consecutiveDhtFaults  = 0;  // DHT returning NaN

// Total fault events (cumulative, never resets)
int totalLampFaults = 0;
int totalTempFaults = 0;
int totalHumFaults  = 0;
int totalDhtFaults  = 0;

// ── Health thresholds ────────────────────────────────────────
const float HIGH_TEMP_THRESHOLD     = 45.0;  // °C — enclosure overheat
const float HIGH_HUMIDITY_THRESHOLD = 85.0;  // % — moisture risk
const float MIN_LAMP_CURRENT        = 0.015; // A — below this = lamp fault

// ── Predictive maintenance ───────────────────────────────────
// Expected current at 100% brightness (calibrate for your lamp)
const float EXPECTED_CURRENT_100 = 0.200; // Amps

// Exponential Moving Average (responds to drops within ~7 readings)
const float EMA_ALPHA  = 0.15;  // weight for newest reading
float avgCurrentEMA    = -1;    // -1 = uninitialised
float avgVoltageEMA    = -1;
int   sampleCount      = 0;

// Operating hours (time LED has been ON)
unsigned long totalOnTimeMs = 0;
unsigned long lastOnCheckMs = 0;

// Voltage stability tracking
float voltageMin = 9999;
float voltageMax = 0;

// ── Timing ────────────────────────────────────────────────────
unsigned long lastTelemetry = 0;
unsigned long lastFirebasePush = 0;
unsigned long lastFirebasePull = 0;

const unsigned long TELEMETRY_INTERVAL   = 1000;
const unsigned long FIREBASE_PUSH_INTERVAL = 2000;
const unsigned long FIREBASE_PULL_INTERVAL = 1500;

static unsigned long lastCommandTimestamp = 0;

// ═════════════════════════════════════════════════════════════
//  SENSOR READING FUNCTIONS
// ═════════════════════════════════════════════════════════════

float readVoltage() {
  int raw = analogRead(VOLT_PIN);
  float v = (raw / 4095.0) * 3.3;
  return v * VOLT_MULTIPLIER;
}

float readCurrent() {
  int raw = analogRead(ACS_PIN);
  float voltage = (raw / 4095.0) * 3.3;
  float current = (voltage - ACS_ZERO_OFFSET) / CURRENT_SENSITIVITY;
  return abs(current);
}

// ═════════════════════════════════════════════════════════════
//  HELPERS
// ═════════════════════════════════════════════════════════════

String ledStatus(int pwm) {
  int percent = map(pwm, 0, 255, 0, 100);
  if (pwm == 0)   return "OFF  0%";
  if (pwm == 255) return "ON   100%";
  return "ON   " + String(percent) + "%";
}

int pwmToPercent(int pwm) { return map(pwm, 0, 255, 0, 100); }

void applyAutoMode(bool isNight) {
  if (!autoMode) return;
  currentPWM = isNight ? AUTO_NIGHT_PWM : 0;
  ledcWrite(LED_PIN, currentPWM);
}

// ═════════════════════════════════════════════════════════════
//  HEALTH ENGINE — evaluates all fault conditions
// ═════════════════════════════════════════════════════════════

HealthStatus evaluateHealth(float temp, float humidity, float current,
                             bool tempErr, bool humErr) {
  HealthStatus h;
  bool ledOn = (currentPWM > 0);

  // ── DHT sensor faults ──
  if (tempErr || humErr) {
    consecutiveDhtFaults++;
    totalDhtFaults++;
  } else {
    consecutiveDhtFaults = 0;
  }

  // ── Temperature ──
  if (tempErr) {
    h.envTempStatus = "FAULT";
  } else if (temp > HIGH_TEMP_THRESHOLD) {
    consecutiveTempFaults++;
    totalTempFaults++;
    h.envTempStatus = (consecutiveTempFaults >= FAULT_THRESHOLD) ? "WARNING" : "FAULT";
  } else {
    consecutiveTempFaults = 0;
    h.envTempStatus = "OK";
  }

  // ── Humidity ──
  if (humErr) {
    h.envHumidityStatus = "FAULT";
  } else if (humidity > HIGH_HUMIDITY_THRESHOLD) {
    consecutiveHumFaults++;
    totalHumFaults++;
    h.envHumidityStatus = (consecutiveHumFaults >= FAULT_THRESHOLD) ? "WARNING" : "FAULT";
  } else {
    consecutiveHumFaults = 0;
    h.envHumidityStatus = "OK";
  }

  // ── Lamp fault ──
  // Fault triggers when:
  //   a) current is below absolute minimum (lamp dead / disconnected), OR
  //   b) current is below 40 % of expected for the brightness level
  float expectedI = (pwmToPercent(currentPWM) / 100.0) * EXPECTED_CURRENT_100;
  bool  currentTooLow = (current < MIN_LAMP_CURRENT) ||
                        (expectedI > 0.01 && current < expectedI * 0.40);

  if (!ledOn) {
    consecutiveLampFaults = 0;
    h.lampStatus = "OFF";
  } else if (currentTooLow) {
    consecutiveLampFaults++;
    totalLampFaults++;
    h.lampStatus = (consecutiveLampFaults >= FAULT_THRESHOLD) ? "WARNING" : "FAULT";
  } else {
    consecutiveLampFaults = 0;
    h.lampStatus = "OK";
  }

  // ── DHT status ──
  h.dhtStatus = (consecutiveDhtFaults >= FAULT_THRESHOLD) ? "WARNING"
              : (tempErr || humErr) ? "FAULT" : "OK";

  // ── Global warning flag ──
  h.warningFlag = (consecutiveLampFaults >= FAULT_THRESHOLD) ||
                  (consecutiveTempFaults >= FAULT_THRESHOLD) ||
                  (consecutiveHumFaults  >= FAULT_THRESHOLD) ||
                  (consecutiveDhtFaults  >= FAULT_THRESHOLD);

  return h;
}

// ═════════════════════════════════════════════════════════════
//  PREDICTIVE MAINTENANCE ENGINE
// ═════════════════════════════════════════════════════════════

PredictiveStatus evaluatePredictive(float current, float voltage) {
  PredictiveStatus p;

  // ── Update EMA averages ──
  if (avgCurrentEMA < 0) {          // first reading
    avgCurrentEMA = current;
    avgVoltageEMA = voltage;
  } else {
    avgCurrentEMA = EMA_ALPHA * current + (1.0 - EMA_ALPHA) * avgCurrentEMA;
    avgVoltageEMA = EMA_ALPHA * voltage + (1.0 - EMA_ALPHA) * avgVoltageEMA;
  }
  sampleCount++;

  p.avgCurrent = avgCurrentEMA;
  p.voltageAvg = avgVoltageEMA;

  // ── Voltage stability ──
  if (voltage < voltageMin) voltageMin = voltage;
  if (voltage > voltageMax) voltageMax = voltage;
  float voltageRange = voltageMax - voltageMin;
  p.powerStability = (voltageRange < 1.0) ? "STABLE" : "UNSTABLE";

  // ── Operating hours ──
  unsigned long now = millis();
  if (currentPWM > 0) {
    if (lastOnCheckMs > 0) {
      totalOnTimeMs += (now - lastOnCheckMs);
    }
    lastOnCheckMs = now;
  } else {
    lastOnCheckMs = 0;
  }
  p.operatingHours = totalOnTimeMs / 3600000.0;

  // ── Lamp health prediction ──
  int brightPercent = pwmToPercent(currentPWM);
  p.expectedCurrent = (brightPercent / 100.0) * EXPECTED_CURRENT_100;

  if (currentPWM == 0) {
    p.lampHealth = "IDLE";
  } else if (p.expectedCurrent > 0.001) {
    float ratio = p.avgCurrent / p.expectedCurrent;
    if (ratio > 0.70)      p.lampHealth = "GOOD";
    else if (ratio > 0.40) p.lampHealth = "DEGRADING";
    else                   p.lampHealth = "REPLACE";
  } else {
    p.lampHealth = "GOOD";
  }

  // ── Maintenance alerts ──
  if (p.lampHealth == "REPLACE") {
    p.maintenanceAlert = "CRITICAL: Lamp drawing very low current — replace lamp";
  } else if (p.lampHealth == "DEGRADING") {
    p.maintenanceAlert = "NOTICE: Lamp output declining — plan replacement";
  } else if (p.powerStability == "UNSTABLE") {
    p.maintenanceAlert = "NOTICE: Voltage fluctuation detected — check power supply";
  } else if (consecutiveTempFaults >= FAULT_THRESHOLD) {
    p.maintenanceAlert = "WARNING: Sustained high temperature — check ventilation";
  } else if (consecutiveHumFaults >= FAULT_THRESHOLD) {
    p.maintenanceAlert = "WARNING: Sustained high humidity — check enclosure seal";
  } else if (consecutiveDhtFaults >= FAULT_THRESHOLD) {
    p.maintenanceAlert = "NOTICE: DHT sensor unreliable — consider replacement";
  } else if (p.operatingHours > 5000) {
    p.maintenanceAlert = "SCHEDULED: 5000+ operating hours — routine inspection due";
  } else {
    p.maintenanceAlert = "NONE";
  }

  return p;
}

// ═════════════════════════════════════════════════════════════
//  FIREBASE — PUSH  →  /SG-NODE2/{Sensor, Actuator, Health, Predictive}
// ═════════════════════════════════════════════════════════════

void pushToFirebase(int ldrRaw, bool isNight, float voltage, float temp,
                    float humidity, float current, bool tempErr, bool humErr) {
  if (WiFi.status() != WL_CONNECTED) return;

  // ── Evaluate systems ──
  HealthStatus health = evaluateHealth(temp, humidity, current, tempErr, humErr);
  PredictiveStatus pred = evaluatePredictive(current, voltage);

  HTTPClient http;
  http.begin(String(FIREBASE_HOST) + NODE_PATH);
  http.addHeader("Content-Type", "application/json");

  StaticJsonDocument<1280> doc;

  // ── Sensor ──
  JsonObject sensor = doc.createNestedObject("Sensor");
  sensor["temperature"] = tempErr ? 0 : round(temp * 10.0) / 10.0;
  sensor["humidity"]    = humErr  ? 0 : round(humidity * 10.0) / 10.0;
  sensor["ldrData"]     = ldrRaw;
  sensor["isNight"]     = isNight;
  sensor["voltage"]     = round(voltage * 1000.0) / 1000.0;
  sensor["current"]     = round(current * 1000.0) / 1000.0;

  // ── Actuator ──
  JsonObject actuator = doc.createNestedObject("Actuator");
  actuator["lightOn"]           = (currentPWM > 0);
  actuator["brightnessPercent"] = pwmToPercent(currentPWM);
  actuator["currentMode"]       = autoMode ? 2 : 1;

  // ── Health ──
  JsonObject h = doc.createNestedObject("Health");
  h["envTempStatus"]       = health.envTempStatus;
  h["envHumidityStatus"]   = health.envHumidityStatus;
  h["lampStatus"]          = health.lampStatus;
  h["dhtStatus"]           = health.dhtStatus;
  h["highTempCounter"]     = consecutiveTempFaults;
  h["highHumidityCounter"] = consecutiveHumFaults;
  h["lampFaultCounter"]    = consecutiveLampFaults;
  h["dhtFaultCounter"]     = consecutiveDhtFaults;
  h["warningFlag"]         = health.warningFlag;

  // ── Predictive Maintenance ──
  JsonObject pm = doc.createNestedObject("Predictive");
  pm["lampHealth"]        = pred.lampHealth;
  pm["avgCurrent"]        = round(pred.avgCurrent * 1000.0) / 1000.0;
  pm["expectedCurrent"]   = round(pred.expectedCurrent * 1000.0) / 1000.0;
  pm["powerStability"]    = pred.powerStability;
  pm["voltageAvg"]        = round(pred.voltageAvg * 1000.0) / 1000.0;
  pm["operatingHours"]    = round(pred.operatingHours * 100.0) / 100.0;
  pm["maintenanceAlert"]  = pred.maintenanceAlert;

  String payload;
  serializeJson(doc, payload);

  int code = http.sendRequest("PATCH", payload);
  if (code > 0)
    Serial.printf("[Firebase] Push OK (%d)\n", code);
  else
    Serial.printf("[Firebase] Push fail: %s\n", http.errorToString(code).c_str());
  http.end();
}

// ═════════════════════════════════════════════════════════════
//  FIREBASE — PULL  ←  /SG-NODE2/Control
// ═════════════════════════════════════════════════════════════

void pullFromFirebase() {
  if (WiFi.status() != WL_CONNECTED) return;

  HTTPClient http;
  http.begin(String(FIREBASE_HOST) + CONTROL_PATH);

  int code = http.GET();
  if (code == 200) {
    String body = http.getString();
    if (body == "null" || body.length() < 3) { http.end(); return; }

    StaticJsonDocument<256> doc;
    if (deserializeJson(doc, body)) { http.end(); return; }

    unsigned long cmdTs = doc["commandTimestamp"] | 0UL;
    int mode            = doc["mode"] | -1;
    int targetBright    = doc["targetBrightness"] | -1;

    if (cmdTs != 0 && cmdTs == lastCommandTimestamp) { http.end(); return; }
    lastCommandTimestamp = cmdTs;

    bool changed = false;

    if (mode == 2 && !autoMode)      { autoMode = true;  changed = true; Serial.println("[CMD] Auto ON");  }
    else if (mode == 1 && autoMode)  { autoMode = false; changed = true; Serial.println("[CMD] Auto OFF"); }

    if (!autoMode && targetBright >= 0) {
      if      (targetBright == 0)    currentPWM = 0;
      else if (targetBright <= 25)   currentPWM = 64;
      else if (targetBright <= 50)   currentPWM = 128;
      else if (targetBright <= 75)   currentPWM = 191;
      else                           currentPWM = 255;
      Serial.printf("[CMD] Brightness %d%%\n", targetBright);
      changed = true;
    }

    if (changed) ledcWrite(LED_PIN, currentPWM);
  }
  http.end();
}

// ═════════════════════════════════════════════════════════════
//  SERIAL COMMAND HANDLER
// ═════════════════════════════════════════════════════════════

void handleSerialCommand(String cmd) {
  cmd.trim();
  cmd.toLowerCase();

  if (cmd == "auto on") {
    autoMode = true;
    Serial.println("[CMD] Auto mode ON");
  } else if (cmd == "auto off") {
    autoMode = false;
    Serial.println("[CMD] Auto mode OFF");
  } else if (cmd == "force on") {
    if (autoMode) { Serial.println("[WARN] Auto active."); return; }
    currentPWM = 255;
    ledcWrite(LED_PIN, currentPWM);
    Serial.println("[CMD] Force ON");
  } else if (cmd == "force off") {
    if (autoMode) { Serial.println("[WARN] Auto active."); return; }
    currentPWM = 0;
    ledcWrite(LED_PIN, currentPWM);
    Serial.println("[CMD] Force OFF");
  } else if (cmd.startsWith("brightness ")) {
    if (autoMode) { Serial.println("[WARN] Auto active."); return; }
    int level = cmd.substring(11).toInt();
    switch (level) {
      case 0:   currentPWM = 0;   break;
      case 25:  currentPWM = 64;  break;
      case 50:  currentPWM = 128; break;
      case 75:  currentPWM = 191; break;
      case 100: currentPWM = 255; break;
      default:  Serial.println("[ERR] Use: 0,25,50,75,100"); return;
    }
    ledcWrite(LED_PIN, currentPWM);
    Serial.printf("[CMD] Brightness %d%%\n", level);
  } else if (cmd == "status") {
    Serial.printf("Mode: %s | PWM: %d | LampFaults: %d/%d\n",
                  autoMode ? "AUTO" : "MANUAL", currentPWM,
                  consecutiveLampFaults, FAULT_THRESHOLD);
  } else {
    Serial.println("[ERR] Commands: auto on/off | force on/off | brightness N | status");
  }
}

// ═════════════════════════════════════════════════════════════
//  TELEMETRY PRINT
// ═════════════════════════════════════════════════════════════

void printTelemetry(bool isNight) {
  int   ldrRaw   = analogRead(LDR_AO_PIN);
  float voltage  = readVoltage();
  float temp     = dht.readTemperature();
  float humid    = dht.readHumidity();
  float current  = readCurrent();

  bool tempErr = isnan(temp);
  bool humErr  = isnan(humid);

  bool ledOn    = (currentPWM > 0);

  Serial.println("--- SENSOR ---");
  Serial.printf("  LDR: %d | Night: %s | V: %.3f\n", ldrRaw, isNight?"Y":"N", voltage);
  Serial.printf("  T: %s | H: %s | I: %.3f A\n",
                tempErr ? "ERR" : String(temp,1).c_str(),
                humErr  ? "ERR" : String(humid,1).c_str(),
                current);

  Serial.println("--- ACTUATOR ---");
  Serial.printf("  LED: %s | Bright: %d%% | Mode: %s\n",
                ledOn?"ON":"OFF", pwmToPercent(currentPWM),
                autoMode?"AUTO":"MANUAL");

  Serial.println("--- HEALTH ---");
  Serial.printf("  Lamp: %d/%d | Temp: %d/%d | Hum: %d/%d | DHT: %d/%d\n",
                consecutiveLampFaults, FAULT_THRESHOLD,
                consecutiveTempFaults, FAULT_THRESHOLD,
                consecutiveHumFaults,  FAULT_THRESHOLD,
                consecutiveDhtFaults,  FAULT_THRESHOLD);

  Serial.printf("  OpHours: %.1f h\n", totalOnTimeMs / 3600000.0);
  Serial.println("---");

  unsigned long now = millis();
  if (now - lastFirebasePush >= FIREBASE_PUSH_INTERVAL) {
    lastFirebasePush = now;
    pushToFirebase(ldrRaw, isNight, voltage, temp, humid, current, tempErr, humErr);
  }
}

// ═════════════════════════════════════════════════════════════
//  SETUP & LOOP
// ═════════════════════════════════════════════════════════════

void setup() {
  Serial.begin(115200);
  dht.begin();

  pinMode(LDR_DO_PIN, INPUT);
  pinMode(LED_PIN, OUTPUT);
  ledcAttach(LED_PIN, PWM_FREQ, PWM_RESOLUTION);

  Serial.printf("\nConnecting to %s", WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  while (WiFi.status() != WL_CONNECTED) { delay(500); Serial.print("."); }
  Serial.printf("\nWiFi OK! IP: %s\n", WiFi.localIP().toString().c_str());
  Serial.println("=== ShineGuard Telemetry Ready ===");
  Serial.println("Commands: auto on/off | force on/off | brightness N | status");
}

void loop() {
  bool isNight = digitalRead(LDR_DO_PIN);

  if (Serial.available()) {
    handleSerialCommand(Serial.readStringUntil('\n'));
  }

  applyAutoMode(isNight);
  ledcWrite(LED_PIN, currentPWM);

  unsigned long now = millis();

  if (now - lastFirebasePull >= FIREBASE_PULL_INTERVAL) {
    lastFirebasePull = now;
    pullFromFirebase();
  }

  if (now - lastTelemetry >= TELEMETRY_INTERVAL) {
    lastTelemetry = now;
    printTelemetry(isNight);
  }
}