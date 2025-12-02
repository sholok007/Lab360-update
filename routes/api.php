
<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
//use App\Http\Controllers\EspSensorController;
use App\Http\Controllers\MachineController;
use App\Http\Controllers\MachineCalibrateController;
//Route::post('/esp-sensor', [EspSensorController::class, 'store']);
use App\Events\NewTerminalData;
use App\Events\DeviceDataReceived;
use App\Models\PendingDeviceCommand;
use App\Models\ReactorCalibrate;
use App\Models\CcCalibrate;

// Health check endpoint
Route::get('/health', function() {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

//Route::post('/machine/connect', [MachineController::class,'connectDevice']);

//Route::post('/machines/connect-device', [MachineController::class, 'connectDevice'])->name('machines.connect-device');

Route::post('/machines/connect-device', [MachineController::class, 'connectDevice'])->name('machines.connect-device');
Route::get('/machines/{mac_id}', [MachineController::class, 'getMachineData'])->name('machines.get-data');

// ESP32 Device Data Endpoint - No authentication required
Route::post('/device/send-data', function(Request $request) {
    $data = $request->validate([
        'mac_id' => 'required|string',
        'cmd' => 'nullable|string',
        'value' => 'nullable',
        'timestamp' => 'nullable|integer',
    ]);

    // Broadcast to connected clients
    event(new DeviceDataReceived([
        'mac_id' => $data['mac_id'],
        'status' => 'ok',
        'message' => 'Data received from device',
        'cmd' => $data['cmd'] ?? null,
        'value' => $data['value'] ?? null,
        'timestamp' => $data['timestamp'] ?? now()->timestamp,
    ]));

    return response()->json(['status' => 'success', 'message' => 'Data received']);
});

// ESP32 Acknowledgment Endpoint - Device confirms command received and processed
Route::post('/device/acknowledge', function(Request $request) {
    try {
        $data = $request->validate([
            'mac_id' => 'required|string',
            'transaction_id' => 'required|string',
            'status' => 'required|in:success,error',
            'message' => 'nullable|string',
            'error' => 'nullable|string',
        ]);

        \Log::info('📥 Acknowledgment received:', $data);

        $pendingCommand = PendingDeviceCommand::where('transaction_id', $data['transaction_id'])
            ->where('mac_id', $data['mac_id'])
            ->first();

        // Always broadcast the ACK data to the WebSocket channel for UI log
        $mac = str_replace(':', '', $data['mac_id']);
        broadcast(new \App\Events\DeviceDataReceived([
            'type' => 'acknowledgment',
            'mac_id' => $data['mac_id'],
            'transaction_id' => $data['transaction_id'],
            'status' => $data['status'],
            'message' => $data['message'],
            'error' => $data['error'],
            'timestamp' => now()->timestamp,
        ]))->toOthers();

        if (!$pendingCommand) {
            // Always return 200 OK to prevent ESP32 retry loop
            return response()->json([
                'status' => 'ok',
                'message' => 'Transaction not found (already acknowledged or invalid)'
            ], 200);
        }

        if ($data['status'] === 'success') {
            $pendingCommand->markAsAcknowledged();
            $payload = $pendingCommand->payload;

            switch ($pendingCommand->command) {
                case 'save_reactor_calibrate':
                    $reactor = ReactorCalibrate::updateOrCreate(
                        ['machine_id' => $payload['machine_id']],
                        ['value' => $payload['value']]
                    );
                    \Log::info('✅ Reactor calibrate saved to DB after acknowledgment', ['id' => $reactor->id]);
                    break;
                case 'save_cc_calibrate':
                    $cc = CcCalibrate::updateOrCreate(
                        ['machine_id' => $payload['machine_id']],
                        ['value' => $payload['value']]
                    );
                    \Log::info('✅ CC calibrate saved to DB after acknowledgment', ['id' => $cc->id]);
                    break;
                case 'save_drain_setup':
                    $drain = MachineDrain::updateOrCreate(
                        ['machine_id' => $payload['machine_id']],
                        [
                            'drain_type' => $payload['drain_type'],
                            'ml_value' => $payload['ml_value'],
                        ]
                    );
                    \Log::info('✅ Drain setup saved to DB after acknowledgment', ['id' => $drain->id]);
                    break;
                case 'save_rodi_setup':
                    $rodi = MachineRodi::updateOrCreate(
                        ['machine_id' => $payload['machine_id']],
                        [
                            'rodi_type' => $payload['rodi_type'],
                            'ml_value' => $payload['ml_value'],
                        ]
                    );
                    \Log::info('✅ RODI setup saved to DB after acknowledgment', ['id' => $rodi->id]);
                    break;
                case 'save_other_settings':
                    $other = MachineOther::updateOrCreate(
                        ['machine_id' => $payload['machine_id']],
                        [
                            'clarity_test' => $payload['clarity_test'] ?? 'OFF',
                            'tem_test' => $payload['tem_test'] ?? 'OFF',
                            'alarm' => $payload['alarm'] ?? 'OFF',
                        ]
                    );
                    \Log::info('✅ Other settings saved to DB after acknowledgment', ['id' => $other->id]);
                    break;
                case 'save_machine_data':
                    $machineData = \App\Models\MachineData::create([
                        'machine_id' => $payload['machine_id'],
                        'chip_id' => $payload['chip_id'],
                        'test_name' => $payload['test_name'],
                        'brand_id' => $payload['brand_id'],
                        'reagent_a_location_id' => $payload['reagent_a_location_id'],
                        'reagent_b_location_id' => $payload['reagent_b_location_id'],
                        'reagent_c_location_id' => $payload['reagent_c_location_id'],
                        'reagent_d_location_id' => $payload['reagent_d_location_id'],
                        'reagent_e_location_id' => $payload['reagent_e_location_id'],
                    ]);
                    \Log::info('✅ Machine data saved to DB after acknowledgment', ['id' => $machineData->id]);
                    break;
                case 'delete_reactor_calibrate':
                    $calibrateId = $payload['calibrate_id'];
                    $deleted = ReactorCalibrate::where('id', $calibrateId)->delete();
                    \Log::info('🗑️ Reactor calibrate deleted from DB after ECHO', ['id' => $calibrateId, 'deleted' => $deleted]);
                    break;
                case 'delete_cc_calibrate':
                    $calibrateId = $payload['calibrate_id'];
                    $deleted = CcCalibrate::where('id', $calibrateId)->delete();
                    \Log::info('🗑️ CC calibrate deleted from DB after ECHO', ['id' => $calibrateId, 'deleted' => $deleted]);
                    break;
            }

            broadcast(new \App\Events\DeviceAcknowledged([
                'mac_id' => $data['mac_id'],
                'status' => 'acknowledged',
                'command' => $pendingCommand->command,
                'transaction_id' => $data['transaction_id'],
                'message' => 'Command acknowledged and saved to database',
            ]));

            return response()->json([
                'status' => 'success',
                'message' => 'Acknowledgment processed and data saved'
            ]);
        }

        // If not success, treat as error/failure
        $pendingCommand->markAsFailed($data['error'] ?? $data['message'] ?? 'Unknown error');
        \Log::error('❌ Device reported error for transaction: ' . $data['transaction_id'], [
            'error' => $data['error'] ?? $data['message']
        ]);
        broadcast(new \App\Events\DeviceAcknowledged([
            'mac_id' => $data['mac_id'],
            'status' => 'failed',
            'command' => $pendingCommand->command,
            'transaction_id' => $data['transaction_id'],
            'message' => 'Device reported error: ' . ($data['error'] ?? $data['message']),
        ]));
        return response()->json([
            'status' => 'error',
            'message' => 'Device reported error',
            'error' => $data['error'] ?? $data['message']
        ], 400);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Acknowledgment processing error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 500);
    }
});

// Send command to ESP32 device
Route::post('/device/send-command', function(Request $request) {
    try {
        $data = $request->validate([
            'mac_id' => 'required|string',
            'command' => 'required|string',
            'payload' => 'nullable|array',
        ]);

        \Log::info('Command received:', $data);

        // Broadcast command to the specific device channel
        try {
            event(new NewTerminalData([
                'mac_id' => $data['mac_id'],
                'command' => $data['command'],
                'payload' => $data['payload'] ?? null,
                'timestamp' => now()->timestamp,
            ]));
        } catch (\Exception $broadcastError) {
            \Log::error('Broadcast error: ' . $broadcastError->getMessage());
            // Continue even if broadcast fails
        }

        return response()->json([
            'status' => 'success', 
            'message' => 'Command sent to device',
            'data' => $data
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Send command error: ' . $e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage()
        ], 400);
    }
});


// Save CC Calibrate (keep only this if not duplicated)


Route::post('/send-command', function(Request $request){
    $data = $request->input('data'); // JSON data
    event(new NewTerminalData($data));
    return response()->json(['status' => 'success', 'data' => $data]);
});

Route::get('/test-event', function() {
    event(new DeviceDataReceived([
        'mac_id' => '8C:4F:00:AC:26:EC',
        'status' => 'ok',
        'message' => 'Test message from Laravel',
        'data' => ['test' => 123]
    ]));

    return 'Event sent!';
});

