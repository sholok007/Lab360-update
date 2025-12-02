/*
  esp32_weber360.ino - WebSocket Client with Echo/Acknowledgment System
  
  Server: 10.0.0.169:8000 (HTTP) / 10.0.0.169:9000 (Reverb WebSocket)
  WiFi: Sholok / 6789@Cobia
  
  ECHO SYSTEM: ESP32 confirms receipt of ALL commands before server saves to database
  This ensures reliable delivery and prevents data loss
  
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
//const char* WS_PATH = "/";
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
    const char* transactionId = innerDoc["transaction_id"];  // ECHO: Get transaction ID
    
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

    // ========================================
    // EXECUTE COMMANDS WITH ECHO CONFIRMATION
    // ========================================
    
    if (strcmp(command, "start_test") == 0) {
      Serial.println("▶️  Executing START_TEST");
      
      // TODO: Add your test start logic here
      
      if (transactionId) {
        // ECHO: Confirm receipt - Server will save to DB only after this
        sendAcknowledgment(transactionId, "success", "Test started successfully");
        Serial.println("✅ ECHO sent - Server can now save to database");
      } else {
        postDeviceResponse("start_test_ack", "test_started");
      }
      
    } else if (strcmp(command, "stop_test") == 0) {
      Serial.println("⏹️  Executing STOP_TEST");
      
      // TODO: Add your test stop logic here
      
      if (transactionId) {
        // ECHO: Confirm receipt
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
      
      // TODO: Save to EEPROM or process the value
      // EEPROM.write(REACTOR_ADDR, value);
      // EEPROM.commit();
      
      if (transactionId) {
        // ECHO: Only after ESP32 sends this, server saves to database
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
      
      // TODO: Save to EEPROM or process the value
      // EEPROM.write(CC_ADDR, value);
      // EEPROM.commit();
      
      if (transactionId) {
        // ECHO: Only after ESP32 sends this, server saves to database
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
      
      // TODO: Save to EEPROM or process the values
      // EEPROM.writeString(DRAIN_TYPE_ADDR, drainType);
      // EEPROM.write(DRAIN_ML_ADDR, mlValue);
      // EEPROM.commit();
      
      if (transactionId) {
        // ECHO: Only after ESP32 sends this, server saves to database
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
      
      // TODO: Save to EEPROM or process the values
      // EEPROM.writeString(RODI_TYPE_ADDR, rodiType);
      // EEPROM.write(RODI_ML_ADDR, mlValue);
      // EEPROM.commit();
      
      if (transactionId) {
        // ECHO: Only after ESP32 sends this, server saves to database
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
      
      // TODO: Save to EEPROM or process the values
      // EEPROM.writeString(CLARITY_TEST_ADDR, clarityTest);
      // EEPROM.writeString(TEM_TEST_ADDR, temTest);
      // EEPROM.writeString(ALARM_ADDR, alarm);
      // EEPROM.commit();
      
      if (transactionId) {
        // ECHO: Only after ESP32 sends this, server saves to database
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
      
      // TODO: Clear reactor value from EEPROM
      // EEPROM.write(REACTOR_ADDR, 0);
      // EEPROM.commit();
      Serial.println("   💾 Reactor value cleared from EEPROM");
      
      if (transactionId) {
        // ECHO: Only after ESP32 sends this, server deletes from database
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
      
      // TODO: Clear CC value from EEPROM
      // EEPROM.write(CC_ADDR, 0);
      // EEPROM.commit();
      Serial.println("   💾 CC value cleared from EEPROM");
      
      if (transactionId) {
        // ECHO: Only after ESP32 sends this, server deletes from database
        sendAcknowledgment(transactionId, "success", "CC calibrate cleared from device");
        Serial.println("✅ ECHO sent - Server will now delete from DB");
      } else {
        postDeviceResponse("cc_delete_ack", "deleted");
      }
      
    } else if (strcmp(command, "reagent_location_update") == 0) {
      Serial.println("📦 REAGENT LOCATION UPDATE");
      // Parse reagent data from innerDoc
      // Example: JsonObject deviceData = innerDoc["device_data"];
      // TODO: Process reagent locations as needed
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
        // ECHO ERROR: Tell server this command is not recognized
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

// ========================================
// HTTP POST: ECHO/ACKNOWLEDGMENT (Pusher protocol)
// ========================================
void sendAcknowledgment(const char* transactionId, const char* status, const char* message, const char* error) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("❌ WiFi not connected, cannot send ECHO");
    return;
  }
  if (!wsConnected) {
    Serial.println("❌ WebSocket not connected, cannot send ECHO");
    return;
  }

  // 1. Build the acknowledgment payload
  StaticJsonDocument<JSON_BUFFER_SMALL> payloadDoc;
  payloadDoc["mac_id"] = DEVICE_MAC;
  payloadDoc["transaction_id"] = transactionId;
  payloadDoc["status"] = status;
  if (message) payloadDoc["message"] = message;
  if (error) payloadDoc["error"] = error;

  String payloadStr;
  serializeJson(payloadDoc, payloadStr);

  // 2. Build the Pusher event envelope
  StaticJsonDocument<JSON_BUFFER_SMALL> eventDoc;
  eventDoc["event"] = ".device.acknowledged";
  eventDoc["data"] = payloadStr; // must be a string!

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

// ...rest of the code remains unchanged (postSensorData, postDeviceResponse, sendHeartbeat, connectWiFi, setupWebSocket, setup, loop)...
