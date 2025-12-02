# ESP32 WebSocket Configuration Guide

## What the ESP32 Should Receive

When you click "Start" for Reactor Calibrate, the ESP32 should receive the following data:

### Connection Details
- **WebSocket URL:** `ws://10.0.0.169:9000/app/localkey?protocol=7&client=js&version=7.0.0`
- **Channel to Subscribe:** `device.8C4F00AC26EC` (MAC address without colons)
- **Event Name:** `device.command`

### Data Structure
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

### For CC Calibrate:
```json
{
  "event": "device.command",
  "channel": "device.8C4F00AC26EC",
  "data": {
    "mac_id": "8C:4F:00:AC:26:EC",
    "command": "start_cc_calibrate",
    "payload": null,
    "timestamp": 1700745123
  }
}
```

---

## ESP32 Code Implementation

### 1. WebSocket Connection (Pusher Protocol)
```cpp
#include <WiFi.h>
#include <WebSocketsClient.h>
#include <ArduinoJson.h>

WebSocketsClient webSocket;

const char* ssid = "YOUR_WIFI";
const char* password = "YOUR_PASSWORD";
const char* mac_id = "8C:4F:00:AC:26:EC";
const char* safe_mac = "8C4F00AC26EC";

void setup() {
  Serial.begin(115200);
  WiFi.begin(ssid, password);
  
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  Serial.println("\nWiFi Connected!");
  
  // Connect to Reverb WebSocket
  webSocket.begin("10.0.0.169", 9000, "/app/localkey?protocol=7&client=js&version=7.0.0");
  webSocket.onEvent(webSocketEvent);
  webSocket.setReconnectInterval(5000);
}

void loop() {
  webSocket.loop();
}

void webSocketEvent(WStype_t type, uint8_t * payload, size_t length) {
  switch(type) {
    case WStype_DISCONNECTED:
      Serial.println("WebSocket Disconnected!");
      break;
      
    case WStype_CONNECTED:
      Serial.println("WebSocket Connected!");
      subscribeToChannel();
      break;
      
    case WStype_TEXT:
      handleMessage(payload, length);
      break;
  }
}

void subscribeToChannel() {
  String channelName = "device." + String(safe_mac);
  
  StaticJsonDocument<256> doc;
  doc["event"] = "pusher:subscribe";
  JsonObject data = doc.createNestedObject("data");
  data["channel"] = channelName;
  
  String message;
  serializeJson(doc, message);
  webSocket.sendTXT(message);
  
  Serial.println("Subscribed to: " + channelName);
}

void handleMessage(uint8_t * payload, size_t length) {
  String message = String((char*)payload);
  Serial.println("Received: " + message);
  
  StaticJsonDocument<512> doc;
  DeserializationError error = deserializeJson(doc, message);
  
  if (error) {
    Serial.println("JSON parse error!");
    return;
  }
  
  const char* event = doc["event"];
  
  // Handle subscription success
  if (String(event) == "pusher_internal:subscription_succeeded") {
    Serial.println("✅ Successfully subscribed to channel!");
    return;
  }
  
  // Handle device commands
  if (String(event) == "device.command") {
    JsonObject data = doc["data"];
    const char* command = data["command"];
    const char* mac = data["mac_id"];
    
    Serial.println("Command: " + String(command));
    
    // Execute commands
    if (String(command) == "start_reactor_calibrate") {
      startReactorCalibrate();
    }
    else if (String(command) == "start_cc_calibrate") {
      startCCCalibrate();
    }
    else {
      Serial.println("Unknown command: " + String(command));
    }
  }
}

void startReactorCalibrate() {
  Serial.println("🚀 Starting Reactor Calibration...");
  // Your reactor calibration code here
  // Example: Start pump, measure volume, etc.
}

void startCCCalibrate() {
  Serial.println("🚀 Starting CC Calibration...");
  // Your CC calibration code here
}
```

---

## Python Alternative (for testing)
```python
import socketio
import asyncio

sio = socketio.AsyncClient()

@sio.event
async def connect():
    print('✅ Connected to WebSocket')
    await sio.emit('pusher:subscribe', {
        'channel': 'device.8C4F00AC26EC'
    })

@sio.on('device.command')
async def on_command(data):
    print(f'📤 Command received: {data}')
    command = data.get('command')
    
    if command == 'start_reactor_calibrate':
        print('🚀 Starting Reactor Calibration')
    elif command == 'start_cc_calibrate':
        print('🚀 Starting CC Calibration')

@sio.event
async def disconnect():
    print('❌ Disconnected from WebSocket')

async def main():
    await sio.connect('ws://10.0.0.169:9000/app/localkey?protocol=7&client=js&version=7.0.0')
    await sio.wait()

if __name__ == '__main__':
    asyncio.run(main())
```

---

## Testing Steps

1. **Test with device-listener.html**
   - Open: http://127.0.0.1:8000/device-listener.html
   - Click Start button in calibrate page
   - You should see the command appear in the listener

2. **Check Reverb Logs**
   ```bash
   php artisan reverb:start --debug
   ```
   - Watch for subscription events
   - Watch for broadcast events

3. **Check Laravel Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   - Look for: "Command received: {mac_id:..., command:...}"

---

## Common Issues

### ESP32 Not Receiving Data
1. **Wrong Channel Name** - Must be `device.8C4F00AC26EC` (no colons)
2. **Wrong Event Name** - Must listen for `device.command` (with dot)
3. **Not Subscribed** - Must send `pusher:subscribe` message after connecting
4. **Wrong WebSocket URL** - Check host IP and port
5. **Firewall** - Ensure port 9000 is accessible

### Debugging Commands
```bash
# Check if Reverb is running
netstat -ano | findstr :9000

# Check Laravel logs
tail -f storage/logs/laravel.log | findstr "Command received"

# Test API endpoint directly
curl -X POST http://127.0.0.1:8000/api/device/send-command \
  -H "Content-Type: application/json" \
  -d '{"mac_id":"8C:4F:00:AC:26:EC","command":"test"}'
```
