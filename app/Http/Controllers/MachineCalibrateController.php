<?php

namespace App\Http\Controllers;
use App\Models\Machine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\ReactorCalibrate;
use App\Models\CcCalibrate;
use App\Models\PendingDeviceCommand;
use App\Events\DeviceDataReceived;

class MachineCalibrateController extends Controller{
        
    /*public function index($id){
        $machine = Machine::findOrFail($id);
        return view('machines.machine-calibrate-setup', compact('machine'));
    }*/

    public function index($id)
        {
            $machine = Machine::findOrFail($id);

            // Get latest single record for each
            $reactorData = ReactorCalibrate::where('machine_id', $id)
                            ->orderBy('created_at','desc')
                            ->first();

            $ccData = CcCalibrate::where('machine_id', $id)
                            ->orderBy('created_at','desc')
                            ->first();

            return view('machines.machine-calibrate-setup', compact('machine','reactorData','ccData'));
        }

       // Delete Reactor (Ajax) - with ECHO confirmation
    public function deleteReactor($id)
        {
            $row = ReactorCalibrate::findOrFail($id);
            $machineId = $row->machine_id;
            
            // Get MAC ID from machine
            $machine = Machine::findOrFail($machineId);
            $macId = $machine->mac_id;
            
            // Generate transaction ID
            $transactionId = PendingDeviceCommand::generateTransactionId();
            
            // Create pending delete command
            PendingDeviceCommand::create([
                'mac_id' => $macId,
                'transaction_id' => $transactionId,
                'command' => 'delete_reactor_calibrate',
                'payload' => [
                    'machine_id' => $machineId,
                    'calibrate_id' => $id,
                ],
                'status' => 'pending',
            ]);
            
            // Broadcast to ESP32 via WebSocket
            broadcast(new DeviceDataReceived([
                'mac_id' => $macId,
                'command' => 'delete_reactor_calibrate',
                'transaction_id' => $transactionId,
                'machine_id' => $machineId,
                'calibrate_id' => $id,
            ]));
            
            \Log::info('🗑️ Delete reactor command sent to ESP32, awaiting ECHO confirmation', [
                'transaction_id' => $transactionId,
                'calibrate_id' => $id,
            ]);

            return response()->json(['status' => 'pending', 'message' => 'Delete command sent to device, awaiting confirmation']);
        }

        // Delete CC (Ajax) - with ECHO confirmation
    public function deleteCc($id)
        {
            $row = CcCalibrate::findOrFail($id);
            $machineId = $row->machine_id;
            
            // Get MAC ID from machine
            $machine = Machine::findOrFail($machineId);
            $macId = $machine->mac_id;
            
            // Generate transaction ID
            $transactionId = PendingDeviceCommand::generateTransactionId();
            
            // Create pending delete command
            PendingDeviceCommand::create([
                'mac_id' => $macId,
                'transaction_id' => $transactionId,
                'command' => 'delete_cc_calibrate',
                'payload' => [
                    'machine_id' => $machineId,
                    'calibrate_id' => $id,
                ],
                'status' => 'pending',
            ]);
            
            // Broadcast to ESP32 via WebSocket
            broadcast(new DeviceDataReceived([
                'mac_id' => $macId,
                'command' => 'delete_cc_calibrate',
                'transaction_id' => $transactionId,
                'machine_id' => $machineId,
                'calibrate_id' => $id,
            ]));
            
            \Log::info('🗑️ Delete CC command sent to ESP32, awaiting ECHO confirmation', [
                'transaction_id' => $transactionId,
                'calibrate_id' => $id,
            ]);

            return response()->json(['status' => 'pending', 'message' => 'Delete command sent to device, awaiting confirmation']);
        }

    public function saveReactor(Request $request, $id){
    $request->validate([
        'reactor_value' => 'required|numeric'
    ]);

    $machine = Machine::findOrFail($id);

    // Generate unique transaction ID for acknowledgment tracking
    $transactionId = PendingDeviceCommand::generateTransactionId();

    // Create pending command (will save to DB only after ESP32 acknowledges)
    $pendingCommand = PendingDeviceCommand::create([
        'mac_id' => $machine->mac_id,
        'command' => 'save_reactor_calibrate',
        'payload' => [
            'value' => (float) $request->reactor_value,
            'machine_id' => $machine->id,
        ],
        'transaction_id' => $transactionId,
        'status' => 'pending',
        'sent_at' => now(),
    ]);

    // Send to ESP32 with transaction_id
    $device_data = [
        'mac_id' => $machine->mac_id,
        'command' => 'save_reactor_calibrate',
        'value' => (float) $request->reactor_value,
        'transaction_id' => $transactionId,
    ];
    broadcast(new DeviceDataReceived($device_data));

    return response()->json([
        'status' => 'pending',
        'message' => 'Command sent to device. Waiting for acknowledgment...',
        'transaction_id' => $transactionId,
        'pending_command_id' => $pendingCommand->id,
    ]);
}


    // CC Calibrate save
    public function saveCc(Request $request, $id)
{
    $request->validate(['cc_value' => 'required|numeric']);

    $machine = Machine::findOrFail($id);

    // Generate unique transaction ID for acknowledgment tracking
    $transactionId = PendingDeviceCommand::generateTransactionId();

    // Create pending command (will save to DB only after ESP32 acknowledges)
    $pendingCommand = PendingDeviceCommand::create([
        'mac_id' => $machine->mac_id,
        'command' => 'save_cc_calibrate',
        'payload' => [
            'value' => (float) $request->cc_value,
            'machine_id' => $machine->id,
        ],
        'transaction_id' => $transactionId,
        'status' => 'pending',
        'sent_at' => now(),
    ]);

    // Send to ESP32 with transaction_id
    $device_data = [
        'mac_id' => $machine->mac_id,
        'command' => 'save_cc_calibrate',
        'value' => (float) $request->cc_value,
        'transaction_id' => $transactionId,
    ];
    broadcast(new DeviceDataReceived($device_data));

    return response()->json([
        'status' => 'pending',
        'message' => 'Command sent to device. Waiting for acknowledgment...',
        'transaction_id' => $transactionId,
        'pending_command_id' => $pendingCommand->id,
    ]);
}
}
