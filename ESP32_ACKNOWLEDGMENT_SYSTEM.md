# ESP32 Acknowledgment System Documentation

## Overview
This system ensures that data sent from the web to ESP32 is **confirmed received** before saving to the database. This prevents data loss and ensures reliable communication.

## How It Works

### Flow Diagram
```
Web/User Action → Laravel Backend → ESP32 Device → Acknowledgment → Database Save
     1. User clicks save         2. Command sent     3. ESP32 receives   4. Data saved
                                    (pending)           & acknowledges      to DB
```

### Detailed Flow

1. **User Sends Command** (e.g., save reactor calibrate value)
   - Frontend calls API: `/api/machines/{id}/reactor-calibrate`
   - Laravel creates a `PendingDeviceCommand` with status = 'pending'
   - Laravel generates unique `transaction_id`
   - Command is broadcasted to ESP32 via WebSocket with `transaction_id`
   - Response to frontend: "Command sent, waiting for acknowledgment..."

2. **ESP32 Receives Command**
   - ESP32 receives command via WebSocket
   - ESP32 processes the command (e.g., saves to EEPROM)
   - ESP32 sends HTTP POST to `/api/device/acknowledge` with:
     - `transaction_id` (from received command)
     - `status`: "success" or "error"
     - `message`: Optional success message
     - `error`: Optional error message

3. **Laravel Processes Acknowledgment**
   - Finds `PendingDeviceCommand` by `transaction_id`
   - If status = "success":
     - Marks command as 'acknowledged'
     - **NOW saves data to actual database** (ReactorCalibrate or CcCalibrate)
     - Broadcasts success to frontend
   - If status = "error":
     - Marks command as 'failed'
     - Does NOT save to database
     - Broadcasts error to frontend

## Database Tables

### `pending_device_commands`
Tracks all commands sent to devices awaiting acknowledgment.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| mac_id | string | Device MAC address |
| command | string | Command name (e.g., "save_reactor_calibrate") |
| payload | text (JSON) | Command data |
| transaction_id | string (unique) | Unique ID for tracking |
| status | enum | 'pending', 'acknowledged', 'failed', 'timeout' |
| sent_at | timestamp | When command was sent |
| acknowledged_at | timestamp | When ESP32 acknowledged |
| error_message | text | Error details if failed |
| retry_count | integer | Number of retry attempts |

## API Endpoints

### POST `/api/machines/{id}/reactor-calibrate`
**Request:**
```json
{
  "reactor_value": 123.45
}
```

**Response (Pending):**
```json
{
  "status": "pending",
  "message": "Command sent to device. Waiting for acknowledgment...",
  "transaction_id": "txn_123abc_1700000000",
  "pending_command_id": 1
}
```

### POST `/api/device/acknowledge`
**ESP32 sends this to confirm receipt**

**Request:**
```json
{
  "mac_id": "8C:4F:00:AC:26:EC",
  "transaction_id": "txn_123abc_1700000000",
  "status": "success",
  "message": "Reactor calibrate value received and saved"
}
```

**Response:**
```json
{
  "status": "success",
  "message": "Acknowledgment processed and data saved"
}
```

## ESP32 Code Changes

### Key Additions:

1. **Transaction ID Handling**
   ```cpp
   const char* transactionId = innerDoc["transaction_id"];
   ```

2. **New Acknowledgment Function**
   ```cpp
   void sendAcknowledgment(const char* transactionId, 
                          const char* status, 
                          const char* message = NULL, 
                          const char* error = NULL)
   ```

3. **Command Execution with ACK**
   ```cpp
   if (strcmp(command, "save_reactor_calibrate") == 0) {
       float value = innerDoc["value"];
       // Process the value...
       
       if (transactionId) {
           sendAcknowledgment(transactionId, "success", 
                            "Reactor calibrate value received and saved");
       }
   }
   ```

## Migration Instructions

1. **Run Migration:**
   ```bash
   php artisan migrate
   ```

2. **Upload ESP32 Code:**
   - Open `esp32_weber360_with_ack.ino` in Arduino IDE
   - Update WiFi credentials if needed
   - Update SERVER_HOST if needed
   - Upload to ESP32

3. **Test the System:**
   - Open machine calibrate page
   - Enter a reactor value and click save
   - Check browser console for "pending" status
   - Check ESP32 Serial Monitor for acknowledgment sent
   - Check database for saved value
   - Check `pending_device_commands` table - status should be 'acknowledged'

## Benefits

✅ **Guaranteed Delivery** - Only saves to DB after ESP32 confirms receipt  
✅ **Error Handling** - Knows immediately if ESP32 failed to receive  
✅ **Retry Logic** - Can retry failed commands  
✅ **Audit Trail** - Complete log of all commands and their status  
✅ **Timeout Detection** - Can identify commands that never got acknowledged  

## Error Handling

### If ESP32 is Offline:
- Command saved as 'pending'
- Frontend shows "waiting for acknowledgment"
- Can implement timeout (e.g., 5 minutes)
- Can mark as 'timeout' and retry later

### If ESP32 Reports Error:
- Command marked as 'failed'
- Error message stored
- Data NOT saved to database
- Frontend notified of failure

## Future Enhancements

1. **Retry Mechanism** - Automatically retry failed/timeout commands
2. **Timeout Handler** - Background job to mark old pending commands as timeout
3. **Command Queue** - Queue multiple commands for offline devices
4. **Priority Commands** - High-priority commands retry more aggressively
5. **Web Dashboard** - View pending/failed commands with retry buttons

## Monitoring

### Check Pending Commands:
```sql
SELECT * FROM pending_device_commands WHERE status = 'pending';
```

### Check Failed Commands:
```sql
SELECT * FROM pending_device_commands 
WHERE status = 'failed' 
ORDER BY created_at DESC;
```

### Check Timeout Commands:
```sql
SELECT * FROM pending_device_commands 
WHERE status = 'pending' 
AND sent_at < NOW() - INTERVAL 5 MINUTE;
```

## Files Modified/Created

### Backend:
- ✅ `database/migrations/2025_11_23_170500_create_pending_device_commands_table.php`
- ✅ `app/Models/PendingDeviceCommand.php`
- ✅ `app/Http/Controllers/MachineCalibrateController.php` (updated)
- ✅ `routes/api.php` (added `/api/device/acknowledge` endpoint)

### ESP32:
- ✅ `esp32_weber360_with_ack.ino` (new file with acknowledgment system)

### Documentation:
- ✅ `ESP32_ACKNOWLEDGMENT_SYSTEM.md` (this file)
