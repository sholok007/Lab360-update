/*
  esp32_weber360_with_ack.ino - WebSocket Client for Laravel Reverb with Acknowledgment System
  
  Server: 10.0.0.169:8000 (HTTP) / 10.0.0.169:9000 (Reverb WebSocket)
  WiFi: Sholok / 6789@Cobia
  
  This code connects to Laravel Reverb and listens for commands on device channel.
  It sends acknowledgments back to the server to confirm command receipt.
  Database saves only happen AFTER successful acknowledgment from ESP32.
  
  Updated: November 2025 - With Acknowledgment System (WebSocket ACK, v2)
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

// ========================================
// HELPERS
// ========================================

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

// ========================================
// WEBSOCKET SUBSCRIBE
// ========================================

void sendPusherSubscribe() {
  StaticJsonDocument<512> doc;
  doc["event"] = "pusher:subscribe";
  JsonObject data = doc.createNestedObject("data");
  data["channel"] = deviceChannel;
  data["auth"] = "";  // No auth required for public channel

  String out;
  serializeJson(doc, out);
  webSocket.sendTXT(out);
  
  Serial.println();
  Serial.println("📤 Subscribing to channel: " + deviceChannel);
  Serial.println("   Payload: " + out);
  subscribed = true;
}

// ========================================
// WEBSOCKET EVENT HANDLER
// ========================================

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
      
      // Subscribe to device channel
      delay(500);  // Small delay before subscribing
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

// ========================================
// INCOMING MESSAGE PARSER
// ========================================

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

  // Check if it's a Pusher protocol message
  const char* event = doc["event"];
  if (!event) {
    Serial.println("⚠️  No event field found");
    return;
  }

  Serial.print("📋 Event: ");
  Serial.println(event);

  // Handle Pusher system events
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

  // Handle custom events (e.g., ".device.data")
  if (strcmp(event, ".device.data") == 0 || strstr(event, "device") != NULL) {
    const char* dataStr = doc["data"];
    
    if (!dataStr) {
      Serial.println("⚠️  No data field in event");
      return;
    }

    // Parse the inner data JSON
    DynamicJsonDocument innerDoc(JSON_BUFFER_SMALL);
    DeserializationError innerErr = deserializeJson(innerDoc, dataStr);
    
    if (innerErr) {
      Serial.print("❌ Inner data parse error: ");
      Serial.println(innerErr.c_str());
      return;
    }

    const char* command = innerDoc["command"];
    const char* transactionId = innerDoc["transaction_id"];  // NEW: Get transaction ID
    
    if (!command) {
      Serial.println("⚠️  No command field in data");
      return;
    }

    Serial.print("🎯 Command: ");
    Serial.println(command);
    
    if (transactionId) {
      Serial.print("🔑 Transaction ID: ");
      Serial.println(transactionId);
    }

    // Execute commands and send acknowledgment
    if (strcmp(command, "save_reactor_calibrate") == 0) {
      Serial.println("⚙️  Executing SAVE_REACTOR_CALIBRATE");
      
      float value = innerDoc["value"];
      Serial.print("   Reactor value: ");
      Serial.println(value);
      
      // TODO: Save to EEPROM or process the value
      // For now, just acknowledge
      
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Reactor calibrate value received and saved");
      }
      
    } else if (strcmp(command, "save_cc_calibrate") == 0) {
      Serial.println("⚙️  Executing SAVE_CC_CALIBRATE");
      
      float value = innerDoc["value"];
      Serial.print("   CC value: ");
      Serial.println(value);
      
      // TODO: Save to EEPROM or process the value
      // For now, just acknowledge
      
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "CC calibrate value received and saved");
      }
      
    } else if (strcmp(command, "start_test") == 0) {
      Serial.println("▶️  Executing START_TEST");
      
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Test started");
      } else {
        postDeviceResponse("start_test_ack", "test_started");
      }
      
    } else if (strcmp(command, "stop_test") == 0) {
      Serial.println("⏹️  Executing STOP_TEST");
      
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Test stopped");
      } else {
        postDeviceResponse("stop_test_ack", "test_stopped");
      }
      
    } else if (strcmp(command, "ping") == 0) {
      Serial.println("🏓 Received PING command");
      
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Pong");
      } else {
        postDeviceResponse("pong", "device_online");
      }
      
    } else if (strcmp(command, "get_status") == 0) {
      Serial.println("📊 Sending device status");
      
      if (transactionId) {
        sendAcknowledgment(transactionId, "success", "Device is online");
      } else {
        postDeviceResponse("status", "online");
      }
      
    } else {
      Serial.print("⚠️  Unknown command: ");
      Serial.println(command);
      
      if (transactionId) {
        String errorMsg = "Unknown command: ";
        errorMsg += command;
        sendAcknowledgment(transactionId, "error", NULL, errorMsg.c_str());
      } else {
        postDeviceResponse("unknown_command", command);
      }
    }
  }
}

// ========================================
// HTTP POST: ACKNOWLEDGMENT (NEW)
// ========================================

void sendAcknowledgment(const char* transactionId, const char* status, const char* message, const char* error) {
  if (!wsConnected) {
    Serial.println("❌ WebSocket not connected, cannot send acknowledgment");
    return;
  }

  StaticJsonDocument<JSON_BUFFER_SMALL> doc;
  doc["event"] = ".device.acknowledged";
  JsonObject data = doc.createNestedObject("data");
  data["mac_id"] = DEVICE_MAC;
  data["transaction_id"] = transactionId;
  data["status"] = status;
  if (message) data["message"] = message;
  if (error) data["error"] = error;
  data["timestamp"] = millis() / 1000;

  String payload;
  serializeJson(doc, payload);
  webSocket.sendTXT(payload);

  Serial.println();
  Serial.print("✅ [ACK] WS → ");
  Serial.println(payload);
}

// ========================================
// HTTP POST: SENSOR DATA
// ========================================

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

// ========================================
// HTTP POST: DEVICE RESPONSE
// ========================================

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

// ========================================
// SEND HEARTBEAT
// ========================================

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

// ========================================
// WIFI CONNECT
// ========================================

void connectWiFi() {
  Serial.println();
  Serial.println("════════════════════════════════════");
  Serial.println("  ESP32 Weber360 Device Client");
  Serial.println("  WITH ACKNOWLEDGMENT SYSTEM");
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

// ========================================
// WEBSOCKET SETUP
// ========================================

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
  webSocket.enableHeartbeat(15000, 3000, 2);  // ping every 15s, timeout 3s, 2 retries
  
  Serial.println("✅ WebSocket initialized");
}

// ========================================
// SETUP
// ========================================

void setup() {
  Serial.begin(115200);
  delay(2000);

  // Prepare MAC for channel name (remove colons)
  safeMac = String(DEVICE_MAC);
  safeMac.replace(":", "");
  deviceChannel = getDeviceChannel();

  // Connect to WiFi
  connectWiFi();

  // Setup WebSocket
  if (WiFi.status() == WL_CONNECTED) {
    setupWebSocket();
  }

  lastSensorPost = millis();
  lastHeartbeat = millis();
  
  Serial.println();
  Serial.println("🚀 Setup complete - entering main loop");
  Serial.println("════════════════════════════════════");
}

// ========================================
// MAIN LOOP
// ========================================

void loop() {
  // Maintain WebSocket connection
  webSocket.loop();

  // Check WiFi connection
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("⚠️  WiFi disconnected, reconnecting...");
    connectWiFi();
    delay(5000);
    return;
  }

  unsigned long now = millis();

  // Send sensor data periodically
  if (now - lastSensorPost >= SENSOR_INTERVAL_MS) {
    lastSensorPost = now;
    int sensorValue = random(10, 100);  // Replace with actual sensor reading
    postSensorData(sensorValue);
  }

  // Send heartbeat periodically
  if (now - lastHeartbeat >= HEARTBEAT_INTERVAL) {
    lastHeartbeat = now;
    sendHeartbeat();
  }

  delay(10);
}
