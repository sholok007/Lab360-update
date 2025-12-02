# ESP32 WebSocket Message Format (Pusher Protocol)

## Current Issue
ESP32 is getting: `pusher:error - Invalid message format`

This happens because the ESP32 is sending messages that don't follow the Pusher protocol format.

## Correct Message Formats

### 1. Subscribe to Channel
```json
{
  "event": "pusher:subscribe",
  "data": {
    "channel": "device.8C4F00AC26EC"
  }
}
```

### 2. Heartbeat/Ping (if needed)
**DON'T send custom heartbeat!** Pusher uses `pusher:ping` and `pusher:pong`

If you must send ping:
```json
{
  "event": "pusher:ping",
  "data": {}
}
```

Server will respond with:
```json
{
  "event": "pusher:pong",
  "data": {}
}
```

### 3. Sending Data TO Laravel (from ESP32)
**Use HTTP POST, NOT WebSocket!**

The ESP32 should send sensor data via HTTP POST to:
`http://10.0.0.169:8000/api/device/send-data`

```json
{
  "mac_id": "8C:4F:00:AC:26:EC",
  "cmd": "SENSOR_READ",
  "value": 82,
  "timestamp": 2110
}
```

### 4. Receiving Commands FROM Laravel
ESP32 listens on WebSocket for:
```json
{
  "event": "device.command",
  "channel": "device.8C4F00AC26EC",
  "data": {
    "mac_id": "8C:4F:00:AC:26:EC",
    "command": "start_reactor_calibrate",
    "payload": null,
    "timestamp": 1700745123
  }
}
```

---

## ESP32 Arduino Code Fix

### Remove Custom Heartbeat
```cpp
// ❌ DON'T DO THIS:
// webSocket.sendTXT("{\"heartbeat\":true}");

// ✅ DO THIS INSTEAD:
// Let Pusher handle ping/pong automatically
// OR use proper Pusher format:
String ping = "{\"event\":\"pusher:ping\",\"data\":{}}";
webSocket.sendTXT(ping);
```

### Correct WebSocket Handler
```cpp
void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
  switch(type) {
    case WStype_DISCONNECTED:
      Serial.println("❌ WebSocket Disconnected!");
      break;
      
    case WStype_CONNECTED:
      Serial.println("✅ WebSocket Connected!");
      subscribeToChannel();
      break;
      
    case WStype_TEXT:
      handleWebSocketMessage(payload, length);
      break;
      
    case WStype_PING:
      Serial.println("🏓 Received PING");
      // WebSocket library handles PONG automatically
      break;
      
    case WStype_PONG:
      Serial.println("🏓 Received PONG");
      break;
  }
}

void subscribeToChannel() {
  // Subscribe to device channel to receive commands
  String subscribe = "{\"event\":\"pusher:subscribe\",\"data\":{\"channel\":\"device.8C4F00AC26EC\"}}";
  webSocket.sendTXT(subscribe);
  Serial.println("📡 Subscribed to device.8C4F00AC26EC");
}

void handleWebSocketMessage(uint8_t * payload, size_t length) {
  String message = String((char*)payload);
  Serial.println("\n📥 WebSocket Message:");
  Serial.println("─────────────────────────────────");
  Serial.println(message);
  Serial.println("─────────────────────────────────");
  
  DynamicJsonDocument doc(1024);
  DeserializationError error = deserializeJson(doc, message);
  
  if (error) {
    Serial.println("❌ JSON parse error!");
    return;
  }
  
  const char* event = doc["event"];
  Serial.print("📋 Event: ");
  Serial.println(event);
  
  // Handle subscription success
  if (String(event) == "pusher_internal:subscription_succeeded") {
    Serial.println("✅ Successfully subscribed to channel!");
    return;
  }
  
  // Handle Pusher pong
  if (String(event) == "pusher:pong") {
    Serial.println("🏓 Pong received");
    return;
  }
  
  // Handle errors
  if (String(event) == "pusher:error") {
    Serial.println("❌ Pusher error received");
    return;
  }
  
  // Handle device commands
  if (String(event) == "device.command") {
    JsonObject data = doc["data"];
    const char* command = data["command"];
    
    Serial.print("🎯 Command: ");
    Serial.println(command);
    
    // Execute commands
    if (String(command) == "start_reactor_calibrate") {
      startReactorCalibrate();
    }
    else if (String(command) == "start_cc_calibrate") {
      startCCCalibrate();
    }
  }
}

void startReactorCalibrate() {
  Serial.println("🚀 Starting Reactor Calibration...");
  // Your calibration code here
}

void startCCCalibrate() {
  Serial.println("🚀 Starting CC Calibration...");
  // Your calibration code here
}
```

### Remove Custom Heartbeat Timer
```cpp
// ❌ DON'T send custom heartbeat messages
// The WebSocket library and Pusher handle keep-alive automatically

void loop() {
  webSocket.loop();
  
  // ❌ Remove this:
  // if (millis() - lastHeartbeat > 30000) {
  //   webSocket.sendTXT("{\"heartbeat\":true}");
  //   lastHeartbeat = millis();
  // }
}
```

---

## Summary of Changes Needed

1. **Remove custom heartbeat** - Pusher handles this
2. **Fix message format** - All WebSocket messages must use `{"event":"...", "data":{...}}` format
3. **Keep HTTP POST for sensor data** - Don't send via WebSocket
4. **Subscribe using Pusher format** - `pusher:subscribe` event
5. **Listen for `device.command` events** - This is what Laravel sends

---

## Test After Fixing

1. ESP32 should show: `✅ Successfully subscribed to channel!`
2. Click Start button in calibrate page
3. ESP32 should receive: `🎯 Command: start_reactor_calibrate`
4. No more `pusher:error` messages
