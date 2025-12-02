// version : 2.0 (ACK via WebSocket, root path)

#include <WiFi.h>
#include <HTTPClient.h>
#include <WebSocketsClient.h>
#include <ArduinoJson.h>

// WiFi Credentials
const char* ssid     = "Sholok";
const char* password = "6789@Cobia";

// Server Settings
const char* SERVER_HOST = "10.0.0.169";
const uint16_t HTTP_PORT = 8000;
const uint16_t WS_PORT = 9000;
const char* WS_PATH = "/app/localkey";  // Use root path for maximum compatibility
const char* APP_KEY = "localkey";

// Device Identity
const char* DEVICE_MAC = "8C:4F:00:AC:26:EC";
String safeMac;
String deviceChannel;

// Timing
const unsigned long SENSOR_INTERVAL_MS = 15000UL;  // 15 sec
const unsigned long HEARTBEAT_INTERVAL = 30000UL;  // 30 sec

// JSON Buffers
const size_t JSON_BUFFER_SMALL = 1024;
const size_t JSON_BUFFER_LARGE = 4096;

// Global State
WebSocketsClient webSocket;
bool wsConnected = false;
bool subscribed = false;
unsigned long lastSensorPost = 0;
unsigned long lastHeartbeat = 0;

// Function Declarations
String getDeviceChannel();
String httpUrl(const char* path);
void sendPusherSubscribe();
void webSocketEvent(WStype_t type, uint8_t * payload, size_t length);
void handleIncomingWebSocketPayload(const String &payload);
void sendAcknowledgment(const char* transactionId);
void sendHeartbeat();
void connectWiFi();
void setupWebSocket();

String getDeviceChannel() {
  return "machine." + safeMac;
}

String httpUrl(const char* path) {
  String u = "http://";
  u += SERVER_HOST;
  if (HTTP_PORT != 80 && HTTP_PORT != 0) {
    u += ":";
    u += String(HTTP_PORT);
  }
  u += path;
  return u;
}

void sendPusherSubscribe() {
  StaticJsonDocument<512> doc;
  doc["event"] = "pusher:subscribe";
  JsonObject data = doc.createNestedObject("data");
  data["channel"] = deviceChannel;
  data["auth"] = "";
  String out;
  serializeJson(doc, out);
  webSocket.sendTXT(out);
  Serial.println();
  Serial.println("📤 Subscribing to channel: " + deviceChannel);
  Serial.println("   Payload: " + out);
  subscribed = true;
}

void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
  switch(type) {
    case WStype_DISCONNECTED:
      wsConnected = false;
      subscribed = false;
      Serial.println();
      Serial.println("⚠️ WebSocket DISCONNECTED");
      Serial.println("   Reconnecting...");
      break;
    case WStype_CONNECTED: {
      wsConnected = true;
      Serial.println();
      Serial.println("✅ WebSocket CONNECTED");
      delay(500);
      sendPusherSubscribe();
      break;
    }
    case WStype_TEXT: {
      String msg = String((char*)payload);
      handleIncomingWebSocketPayload(msg);
      break;
    }
    case WStype_PING:
      Serial.println("🏓 Received PING");
      break;
    case WStype_PONG:
      Serial.println("🏓 Received PONG");
      break;
    case WStype_ERROR:
      Serial.println("❌ WebSocket ERROR");
      break;
    default:
      break;
  }
}

void handleIncomingWebSocketPayload(const String &payload) {
  Serial.println();
  Serial.println("📥 WebSocket Message:");
  Serial.println("─────────────────────────────────");
  Serial.println(payload);
  Serial.println("─────────────────────────────────");

  DynamicJsonDocument doc(JSON_BUFFER_LARGE);
  DeserializationError err = deserializeJson(doc, payload);
  if (err) {
    Serial.print("❌ JSON parse error: ");
    Serial.println(err.c_str());
    return;
  }

  const char* event = doc["event"];
  if (!event) return;

  if (strcmp(event, "device.data") == 0) {
    const char* dataStr = doc["data"];
    if (!dataStr) return;

    DynamicJsonDocument innerDoc(JSON_BUFFER_LARGE);
    DeserializationError innerErr = deserializeJson(innerDoc, dataStr);
    if (innerErr) {
      Serial.print("❌ Inner JSON parse error: ");
      Serial.println(innerErr.c_str());
      return;
    }

    const char* transactionId = innerDoc["data"]["transaction_id"];
    if (transactionId) {
      sendAcknowledgment(transactionId);
    }
  }
}

void sendAcknowledgment(const char* transactionId) {
  unsigned long timestamp = millis() / 1000;
  String url = httpUrl("/api/device/acknowledge");

  StaticJsonDocument<JSON_BUFFER_SMALL> doc;
  doc["mac_id"] = DEVICE_MAC;
  doc["transaction_id"] = transactionId;
  doc["status"] = "success";
  doc["message"] = "Command acknowledged";
  doc["error"] = "";

  String payload;
  serializeJson(doc, payload);

  Serial.println();
  Serial.println("🔄 Sending ACK via HTTP POST");
  Serial.println("POST " + url);
  Serial.println(payload);

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(url);
    http.addHeader("Content-Type", "application/json");
    int httpResponseCode = http.POST(payload);
    Serial.print("HTTP Response code: ");
    Serial.println(httpResponseCode);
    if (httpResponseCode > 0) {
      String response = http.getString();
      Serial.println("Server response: " + response);
    }
    http.end();
  } else {
    Serial.println("❌ WiFi not connected. Cannot send ACK.");
  }
}

void sendHeartbeat() {
  if (wsConnected && subscribed) {
    StaticJsonDocument<256> doc;
    doc["event"] = "pusher:ping";
    doc["data"] = "{}";
    String out;
    serializeJson(doc, out);
    webSocket.sendTXT(out);
    Serial.println("💓 Heartbeat sent");
  }
}

void connectWiFi() {
  Serial.println();
  Serial.println("📡 Connecting to WiFi...");
  WiFi.mode(WIFI_STA);
  WiFi.begin(ssid, password);
  unsigned long start = millis();
  int dots = 0;
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
    dots++;
    if (dots % 40 == 0) Serial.println();
    if (millis() - start > 20000) {
      Serial.println();
      Serial.println("❌ WiFi connection timeout");
      return;
    }
  }
  Serial.println();
  Serial.println("✅ WiFi CONNECTED");
  Serial.print("   IP: "); Serial.println(WiFi.localIP());
  Serial.print("   MAC: "); Serial.println(WiFi.macAddress());
}

void setupWebSocket() {
  Serial.println();
  Serial.println("🔌 Initializing WebSocket...");
  webSocket.begin(SERVER_HOST, WS_PORT, WS_PATH);
  webSocket.onEvent(webSocketEvent);
  webSocket.setReconnectInterval(5000);
  webSocket.enableHeartbeat(15000, 3000, 2);
  Serial.println("✅ WebSocket initialized");
}

void setup() {
  Serial.begin(115200);
  delay(2000);
  safeMac = String(DEVICE_MAC);
  safeMac.replace(":", "");
  deviceChannel = getDeviceChannel();
  connectWiFi();
  if (WiFi.status() == WL_CONNECTED) {
    setupWebSocket();
  }
  lastHeartbeat = millis();
  Serial.println();
  Serial.println("🚀 Setup complete");
}

void loop() {
  webSocket.loop();

  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠️ WiFi disconnected, reconnecting...");
    connectWiFi();
    delay(5000);
  } else {
    unsigned long now = millis();

    if (now - lastHeartbeat >= HEARTBEAT_INTERVAL) {
      lastHeartbeat = now;
      sendHeartbeat();
    }
  }

  delay(10);
}
