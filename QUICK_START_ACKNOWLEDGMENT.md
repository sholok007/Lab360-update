# 🚀 ESP32 Acknowledgment System - Quick Start Guide

## 📋 What Was Implemented

A complete acknowledgment system that ensures ESP32 receives commands BEFORE saving data to the database.

### ✅ Files Created/Modified:

**Backend:**
1. `database/migrations/2025_11_23_170500_create_pending_device_commands_table.php` - Tracks pending commands
2. `app/Models/PendingDeviceCommand.php` - Model for pending commands
3. `app/Http/Controllers/MachineCalibrateController.php` - Updated to use pending commands
4. `routes/api.php` - Added `/api/device/acknowledge` endpoint
5. `app/Console/Commands/CleanupPendingCommands.php` - Cleanup command for old pending commands

**ESP32:**
6. `esp32_weber360_with_ack.ino` - ESP32 code with acknowledgment system

**Documentation:**
7. `ESP32_ACKNOWLEDGMENT_SYSTEM.md` - Complete documentation
8. `public/test-acknowledgment.html` - Test page

## 🎯 How to Test

### Step 1: Migration Already Done ✅
```bash
php artisan migrate
```

### Step 2: Upload ESP32 Code
1. Open Arduino IDE
2. Open `esp32_weber360_with_ack.ino`
3. Verify WiFi settings:
   - SSID: "Sholok"
   - Password: "6789@Cobia"
   - Server: "10.0.0.169"
4. Upload to ESP32
5. Open Serial Monitor (115200 baud)

### Step 3: Test via Web Interface

**Option A: Use Test Page**
```
http://localhost:8000/test-acknowledgment.html
```

**Option B: Use Machine Calibrate Page**
1. Navigate to machine calibrate setup page
2. Enter a reactor or CC value
3. Click Save
4. Watch for "waiting for acknowledgment" message

### Step 4: Monitor the Process

**ESP32 Serial Monitor - You Should See:**
```
📥 WebSocket Message:
─────────────────────────────────
{"event":".device.data","data":"{\"mac_id\":\"8C:4F:00:AC:26:EC\",\"command\":\"save_reactor_calibrate\",\"value\":123.45,\"transaction_id\":\"txn_12345_1700000000\"}"}
─────────────────────────────────
📋 Event: .device.data
🎯 Command: save_reactor_calibrate
🔑 Transaction ID: txn_12345_1700000000
⚙️  Executing SAVE_REACTOR_CALIBRATE
   Reactor value: 123.45

✅ [ACK] POST → http://10.0.0.169:8000/api/device/acknowledge
   Payload: {"mac_id":"8C:4F:00:AC:26:EC","transaction_id":"txn_12345_1700000000","status":"success","message":"Reactor calibrate value received and saved"}
   Response: HTTP 200 ✅ {"status":"success","message":"Acknowledgment processed and data saved"}
```

**Laravel Log - You Should See:**
```
📥 Acknowledgment received: {
    "mac_id": "8C:4F:00:AC:26:EC",
    "transaction_id": "txn_12345_1700000000",
    "status": "success",
    "message": "Reactor calibrate value received and saved"
}
✅ Reactor calibrate saved to DB after acknowledgment: {"id": 1}
```

## 🔍 Database Verification

**Check Pending Commands:**
```sql
SELECT * FROM pending_device_commands ORDER BY created_at DESC LIMIT 10;
```

**Expected Status Flow:**
```
Initial:        status = 'pending'
After ACK:      status = 'acknowledged', acknowledged_at = [timestamp]
If Failed:      status = 'failed', error_message = [error details]
If Timeout:     status = 'timeout'
```

**Check Actual Data Tables:**
```sql
-- Should ONLY have data after acknowledgment
SELECT * FROM reactor_calibrates ORDER BY created_at DESC LIMIT 5;
SELECT * FROM cc_calibrates ORDER BY created_at DESC LIMIT 5;
```

## 🛠️ Useful Commands

**Cleanup Old Pending Commands:**
```bash
php artisan device:cleanup-pending --timeout=5
```

**View All Pending Commands:**
```bash
php artisan tinker
>>> App\Models\PendingDeviceCommand::pending()->get();
```

**Manually Mark as Timeout:**
```bash
php artisan tinker
>>> $cmd = App\Models\PendingDeviceCommand::find(1);
>>> $cmd->markAsTimeout();
```

## 🎨 Frontend Integration Tips

### Using Laravel Echo (Real-time Updates)

```javascript
// Listen for acknowledgment events
Echo.channel('machine.' + macId)
    .listen('.device.data', (e) => {
        if (e.status === 'acknowledged') {
            console.log('✅ Command acknowledged!', e);
            showSuccess('Data saved successfully!');
        } else if (e.status === 'failed') {
            console.error('❌ Command failed!', e);
            showError(e.message);
        }
    });
```

### Polling for Status

```javascript
async function checkCommandStatus(transactionId) {
    // Create an endpoint that returns pending command status
    const response = await fetch(`/api/pending-commands/${transactionId}`);
    const data = await response.json();
    
    if (data.status === 'acknowledged') {
        return 'success';
    } else if (data.status === 'failed') {
        return 'error';
    } else {
        return 'pending';
    }
}
```

## 🐛 Troubleshooting

### ESP32 Not Sending Acknowledgment?
1. Check Serial Monitor - is ESP32 receiving the command?
2. Check WiFi connection - is ESP32 connected?
3. Check server host - is it correct (10.0.0.169)?
4. Check HTTP port - should be 8000

### Commands Stuck in Pending?
```bash
# Check pending commands
php artisan tinker
>>> PendingDeviceCommand::where('status', 'pending')->get();

# Mark specific command as failed manually
>>> $cmd = PendingDeviceCommand::find(1);
>>> $cmd->markAsFailed('Manual timeout');
```

### Data Not Saving to Database?
1. Check if acknowledgment was received (check `pending_device_commands.status`)
2. Check Laravel logs for errors
3. Verify machine_id exists in machines table

## 📊 Flow Summary

```
┌─────────────┐
│   Web UI    │ User clicks "Save Reactor: 123.45"
└──────┬──────┘
       │
       ▼
┌─────────────────────────────────────────────────┐
│  MachineCalibrateController::saveReactor()      │
│  - Create PendingDeviceCommand (status=pending) │
│  - Generate transaction_id                      │
│  - Broadcast to ESP32 via WebSocket             │
│  - Return "pending" response                    │
└──────────────────────┬──────────────────────────┘
                       │
                       ▼
              ┌────────────────┐
              │  ESP32 Device  │
              │  - Receive cmd │
              │  - Process it  │
              └────────┬───────┘
                       │
                       ▼ POST /api/device/acknowledge
┌─────────────────────────────────────────────────┐
│  Acknowledgment Endpoint                        │
│  - Find PendingDeviceCommand by transaction_id  │
│  - If success:                                  │
│    * Mark as 'acknowledged'                     │
│    * SAVE to reactor_calibrates table ✅        │
│    * Broadcast success to frontend              │
│  - If error:                                    │
│    * Mark as 'failed'                           │
│    * Do NOT save to DB ❌                       │
│    * Broadcast error to frontend                │
└─────────────────────────────────────────────────┘
```

## ✨ Benefits

- ✅ **Data Integrity:** Only saves when ESP32 confirms receipt
- ✅ **Error Detection:** Immediate feedback if command fails
- ✅ **Audit Trail:** Complete log of all commands and status
- ✅ **Reliability:** Can detect and handle timeouts
- ✅ **Debuggability:** Easy to track command flow

---

**Need Help?** Check `ESP32_ACKNOWLEDGMENT_SYSTEM.md` for detailed documentation.
