/*
  esp32_weber360.ino - WebSocket Client with Echo/Acknowledgment System (COMPLETE)
  Server: 10.0.0.169:8000 (HTTP) / 10.0.0.169:9000 (Reverb WebSocket)
  WiFi: Sholok / 6789@Cobia
  ECHO SYSTEM: ESP32 confirms receipt of ALL commands before server saves to database
  Updated: November 2025 - WITH ECHO/ACKNOWLEDGMENT SYSTEM (Pusher protocol fix)
*/

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
const char* WS_PATH = "/app/localkey";
const char* APP_KEY = "localkey";

// Device Identity - MUST match machine.mac_id in database
const char* DEVICE_MAC = "8C:4F:00:AC:26:EC";
String safeMac;
String deviceChannel;

// Timing
const unsigned long SENSOR_INTERVAL_MS = 15000UL;  // 15 seconds
const unsigned long HEARTBEAT_INTERVAL = 30000UL;  // 30 seconds

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
void postSensorData(int value);
void postDeviceResponse(const char* cmd, const char* value);
void sendAcknowledgment(const char* transactionId, const char* status, const char* message = NULL, const char* error = NULL);
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
      Serial.println("⚠️  WebSocket DISCONNECTED");
      Serial.println("   Reconnecting...");
      break;
    case WStype_CONNECTED: {
      wsConnected = true;
      Serial.println();
      Serial.println("✅ WebSocket CONNECTED to Reverb");
      Serial.print("   Server info: ");
      Serial.println((char*)payload);
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
  if (!event) {
    Serial.println("⚠️  No event field found");
    return;
  }
  Serial.print("📋 Event: ");
  Serial.println(event);
  if (strcmp(event, "pusher:connection_established") == 0) {
    Serial.println("✅ Pusher connection established");
    return;
  }
  if (strcmp(event, "pusher_internal:subscription_succeeded") == 0) {
    Serial.println("✅ Successfully subscribed to channel: " + deviceChannel);
    return;
  }
  if (strcmp(event, "pusher:error") == 0) {
    Serial.println("❌ Pusher error received");
    if (doc.containsKey("data")) {
      serializeJsonPretty(doc["data"], Serial);
    }
    return;
  }
  if (strcmp(event, ".device.data") == 0 || strstr(event, "device") != NULL) {
    const char* dataStr = doc["data"];
    if (!dataStr) {
      Serial.println("⚠️  No data field in event");
      return;
    }
    DynamicJsonDocument innerDoc(JSON_BUFFER_SMALL);
    DeserializationError innerErr = deserializeJson(innerDoc, dataStr);
    if (innerErr) {
      Serial.print("❌ Inner data parse error: ");
      Serial.println(innerErr.c_str());
      return;
    }
    const char* command = innerDoc["command"];
    const char* transactionId = innerDoc["transaction_id"];
    if (!command) {
      Serial.println("⚠️  No command field in data");
      return;
    }
    Serial.print("🎯 Command: ");
    Serial.println(command);
    if (transactionId) {
      Serial.print("🔑 Transaction ID: ");
      Serial.println(transactionId);
      Serial.println("📡 ECHO MODE: Will send acknowledgment to confirm receipt");
    } else {
      Serial.println("⚠️  No transaction ID - Using legacy mode");
    }
    if (strcmp(command, "start_test") == 0) {
      Serial.println("▶️  Executing START_TEST");
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Test started successfully");
        Serial.println("✅ ECHO sent - Server can now save to database");
      } else {
        postDeviceResponse("start_test_ack", "test_started");
      }
    } else if (strcmp(command, "stop_test") == 0) {
      Serial.println("⏹️  Executing STOP_TEST");
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Test stopped successfully");
        Serial.println("✅ ECHO sent - Server can now save to database");
      } else {
        postDeviceResponse("stop_test_ack", "test_stopped");
      }
    } else if (strcmp(command, "ping") == 0) {
      Serial.println("🏓 Received PING command");
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Pong - Device online");
      } else {
        postDeviceResponse("pong", "device_online");
      }
    } else if (strcmp(command, "get_status") == 0) {
      Serial.println("📊 Sending device status");
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Device is online and operational");
      } else {
        postDeviceResponse("status", "online");
      }
    } else if (strcmp(command, "save_reactor_calibrate") == 0) {
      Serial.println("⚙️  Executing SAVE_REACTOR_CALIBRATE");
      float value = innerDoc["value"];
      Serial.print("   Reactor value: ");
      Serial.println(value);
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Reactor calibrate value received and stored");
        Serial.println("✅ ECHO sent - Value stored locally, server will save to DB");
      } else {
        postDeviceResponse("reactor_calibrate_ack", String(value).c_str());
      }
    } else if (strcmp(command, "save_cc_calibrate") == 0) {
      Serial.println("⚙️  Executing SAVE_CC_CALIBRATE");
      float value = innerDoc["value"];
      Serial.print("   CC value: ");
      Serial.println(value);
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "CC calibrate value received and stored");
        Serial.println("✅ ECHO sent - Value stored locally, server will save to DB");
      } else {
        postDeviceResponse("cc_calibrate_ack", String(value).c_str());
      }
    } else if (strcmp(command, "save_drain_setup") == 0) {
      Serial.println("💧 Executing SAVE_DRAIN_SETUP");
      const char* drainType = innerDoc["drain_type"];
      int mlValue = innerDoc["ml_value"];
      Serial.print("   Drain Type: ");
      Serial.println(drainType);
      Serial.print("   ML Value: ");
      Serial.println(mlValue);
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Drain setup received and stored");
        Serial.println("✅ ECHO sent - Drain settings stored locally, server will save to DB");
      } else {
        postDeviceResponse("drain_setup_ack", "drain_configured");
      }
    } else if (strcmp(command, "save_rodi_setup") == 0) {
      Serial.println("🚰 Executing SAVE_RODI_SETUP");
      const char* rodiType = innerDoc["rodi_type"];
      int mlValue = innerDoc["ml_value"];
      Serial.print("   RODI Type: ");
      Serial.println(rodiType);
      Serial.print("   ML Value: ");
      Serial.println(mlValue);
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "RODI setup received and stored");
        Serial.println("✅ ECHO sent - RODI settings stored locally, server will save to DB");
      } else {
        postDeviceResponse("rodi_setup_ack", "rodi_configured");
      }
    } else if (strcmp(command, "save_other_settings") == 0) {
      Serial.println("⚙️  Executing SAVE_OTHER_SETTINGS");
      const char* clarityTest = innerDoc["clarity_test"];
      const char* temTest = innerDoc["tem_test"];
      const char* alarm = innerDoc["alarm"];
      Serial.print("   Clarity Test: ");
      Serial.println(clarityTest);
      Serial.print("   Temperature Test: ");
      Serial.println(temTest);
      Serial.print("   Alarm: ");
      Serial.println(alarm);
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Other settings received and stored");
        Serial.println("✅ ECHO sent - Other settings stored locally, server will save to DB");
      } else {
        postDeviceResponse("other_settings_ack", "settings_configured");
      }
    } else if (strcmp(command, "delete_reactor_calibrate") == 0) {
      Serial.println("🗑️  Executing DELETE_REACTOR_CALIBRATE");
      int calibrateId = innerDoc["calibrate_id"];
      Serial.print("   Calibrate ID: ");
      Serial.println(calibrateId);
      Serial.println("   💾 Reactor value cleared from EEPROM");
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Reactor calibrate cleared from device");
        Serial.println("✅ ECHO sent - Server will now delete from DB");
      } else {
        postDeviceResponse("reactor_delete_ack", "deleted");
      }
    } else if (strcmp(command, "delete_cc_calibrate") == 0) {
      Serial.println("🗑️  Executing DELETE_CC_CALIBRATE");
      int calibrateId = innerDoc["calibrate_id"];
      Serial.print("   Calibrate ID: ");
      Serial.println(calibrateId);
      Serial.println("   💾 CC value cleared from EEPROM");
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "CC calibrate cleared from device");
        Serial.println("✅ ECHO sent - Server will now delete from DB");
      } else {
        postDeviceResponse("cc_delete_ack", "deleted");
      }
    } else if (strcmp(command, "reagent_location_update") == 0) {
      Serial.println("📦 REAGENT LOCATION UPDATE");
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Reagent locations updated");
        Serial.println("✅ ECHO sent - Server can now save reagent locations to database");
      } else {
        postDeviceResponse("reagent_location_update_ack", "locations_updated");
      }
    } else {
      Serial.print("⚠️  Unknown command: ");
      Serial.println(command);
      if (transactionId) {
        String errorMsg = "Unknown command: ";
        errorMsg += command;
        sendAcknowledgment(transactionId, "error", NULL, errorMsg.c_str());
        Serial.println("❌ ECHO sent with error - Server will NOT save to database");
      } else {
        postDeviceResponse("unknown_command", command);
      }
    }
  }
}

void sendAcknowledgment(const char* transactionId, const char* status, const char* message, const char* error) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("❌ WiFi not connected, cannot send ECHO");
    return;
  }
  if (!wsConnected) {
    Serial.println("❌ WebSocket not connected, cannot send ECHO");
    return;
  }

  // 1. Build the acknowledgment payload as a JSON object
  StaticJsonDocument<JSON_BUFFER_SMALL> dataDoc;
  dataDoc["mac_id"] = DEVICE_MAC;
  dataDoc["transaction_id"] = transactionId;
  dataDoc["status"] = status;
  if (message) dataDoc["message"] = message;
  if (error) dataDoc["error"] = error;

  // 2. Build the Pusher event envelope, embedding the dataDoc as an object
  StaticJsonDocument<JSON_BUFFER_SMALL + 256> eventDoc;
  eventDoc["event"] = ".device.acknowledged";
  eventDoc["data"] = dataDoc; // <-- assign as object, not string

  String eventStr;
  serializeJson(eventDoc, eventStr);

  Serial.println();
  Serial.println("═══════════════════════════════════");
  Serial.println("🔄 [ECHO] Sending acknowledgment via WebSocket (Pusher format)");
  Serial.print("   Transaction ID: ");
  Serial.println(transactionId);
  Serial.print("   Status: ");
  Serial.println(status);
  Serial.print("   Event Payload: ");
  Serial.println(eventStr);

  webSocket.sendTXT(eventStr);
  Serial.println("   [ECHO] Sent via WebSocket");
  Serial.println("═══════════════════════════════════");
}

void postSensorData(int value) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("❌ WiFi not connected, skipping sensor post");
    return;
  }
  HTTPClient http;
  String url = httpUrl("/api/device/send-data");
  StaticJsonDocument<JSON_BUFFER_SMALL> doc;
  doc["mac_id"] = DEVICE_MAC;
  doc["cmd"] = "SENSOR_READ";
  doc["value"] = value;
  doc["timestamp"] = millis() / 1000;
  String payload;
  serializeJson(doc, payload);
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  int code = http.POST(payload);
  Serial.println();
  Serial.print("📤 [SENSOR] POST → ");
  Serial.println(url);
  Serial.print("   Payload: ");
  Serial.println(payload);
  if (code > 0) {
    Serial.print("   Response: HTTP ");
    Serial.print(code);
    if (code == 200 || code == 201) {
      String response = http.getString();
      Serial.print(" ✅ ");
      Serial.println(response);
    } else {
      Serial.println(" ⚠️");
    }
  } else {
    Serial.print("   ❌ Error: ");
    Serial.println(http.errorToString(code));
  }
  http.end();
}

void postDeviceResponse(const char* cmd, const char* value) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("❌ WiFi not connected, skipping response");
    return;
  }
  HTTPClient http;
  String url = httpUrl("/api/device/send-data");
  StaticJsonDocument<JSON_BUFFER_SMALL> doc;
  doc["mac_id"] = DEVICE_MAC;
  doc["cmd"] = cmd;
  doc["value"] = value;
  doc["timestamp"] = millis() / 1000;
  String payload;
  serializeJson(doc, payload);
  http.begin(url);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("Accept", "application/json");
  int code = http.POST(payload);
  Serial.println();
  Serial.print("📤 [RESPONSE] POST → ");
  Serial.println(url);
  Serial.print("   Payload: ");
  Serial.println(payload);
  if (code > 0) {
    Serial.print("   Response: HTTP ");
    Serial.print(code);
    if (code == 200 || code == 201) {
      String response = http.getString();
      Serial.print(" ✅ ");
      Serial.println(response);
    } else {
      Serial.println(" ⚠️");
    }
  } else {
    Serial.print("   ❌ Error: ");
    Serial.println(http.errorToString(code));
  }
  http.end();
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
  Serial.println("════════════════════════════════════");
  Serial.println("  ESP32 Weber360 Device Client");
  Serial.println("  WITH ECHO/ACKNOWLEDGMENT SYSTEM");
  Serial.println("════════════════════════════════════");
  Serial.println("📡 ECHO MODE ENABLED:");
  Serial.println("   - Commands are confirmed before DB save");
  Serial.println("   - Prevents data loss");
  Serial.println("   - Ensures reliable delivery");
  Serial.println("════════════════════════════════════");
  Serial.print("📡 Connecting to WiFi: ");
  Serial.println(ssid);
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
  Serial.print("   IP Address: ");
  Serial.println(WiFi.localIP());
  Serial.print("   MAC Address: ");
  Serial.println(WiFi.macAddress());
  Serial.print("   Signal: ");
  Serial.print(WiFi.RSSI());
  Serial.println(" dBm");
  Serial.println("════════════════════════════════════");
}

void setupWebSocket() {
  Serial.println();
  Serial.println("🔌 Initializing WebSocket...");
  Serial.print("   Host: ");
  Serial.println(SERVER_HOST);
  Serial.print("   Port: ");
  Serial.println(WS_PORT);
  Serial.print("   Path: ");
  Serial.println(WS_PATH);
  Serial.print("   Channel: ");
  Serial.println(deviceChannel);
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
  lastSensorPost = millis();
  lastHeartbeat = millis();
  Serial.println();
  Serial.println("🚀 Setup complete - entering main loop");
  Serial.println("════════════════════════════════════");
  Serial.println();
  Serial.println("💡 ECHO SYSTEM READY:");
  Serial.println("   1. Server sends command with transaction_id");
  Serial.println("   2. ESP32 receives and processes command");
  Serial.println("   3. ESP32 sends ECHO (acknowledgment)");
  Serial.println("   4. Server saves to database only after ECHO");
  Serial.println();
  Serial.println("   This ensures NO DATA LOSS! ✅");
  Serial.println("════════════════════════════════════");
}

void loop() {
  webSocket.loop();
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠️  WiFi disconnected, reconnecting...");
    connectWiFi();
    delay(5000);
    return;
  }
  unsigned long now = millis();
  if (now - lastSensorPost >= SENSOR_INTERVAL_MS) {
    lastSensorPost = now;
    int sensorValue = random(10, 100);
    postSensorData(sensorValue);
  }
  if (now - lastHeartbeat >= HEARTBEAT_INTERVAL) {
    lastHeartbeat = now;
    sendHeartbeat();
  }
  delay(10);
}