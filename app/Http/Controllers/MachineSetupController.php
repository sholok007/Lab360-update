<?php

namespace App\Http\Controllers;

use App\Models\Machine;
use App\Models\MachineDrain;
use App\Models\MachineRodi;
use App\Models\MachineOther;
use App\Models\PendingDeviceCommand;
use App\Events\DeviceDataReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MachineSetupController extends Controller{
   
    public function index($id){
        $machine = Machine::findOrFail($id);
        return view('machines.machine-setup', compact('machine'));
    }
    
    public function saveDrain(Request $request){
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'drain_type' => 'required|in:pipe,container',
            'ml_value' => 'nullable|numeric'
        ]);
        
        $machine = Machine::findOrFail($request->machine_id);
        
        // Generate unique transaction ID for ECHO tracking
        $transactionId = PendingDeviceCommand::generateTransactionId();
        
        // Create pending command (will save to DB only after ESP32 ECHO)
        $pendingCommand = PendingDeviceCommand::create([
            'mac_id' => $machine->mac_id,
            'command' => 'save_drain_setup',
            'payload' => [
                'machine_id' => $request->machine_id,
                'drain_type' => $request->drain_type,
                'ml_value' => $request->ml_value,
            ],
            'transaction_id' => $transactionId,
            'status' => 'pending',
            'sent_at' => now(),
        ]);
        
        // Send to ESP32 with transaction_id for ECHO
        $device_data = [
            'mac_id' => $machine->mac_id,
            'command' => 'save_drain_setup',
            'drain_type' => $request->drain_type,
            'ml_value' => $request->ml_value,
            'transaction_id' => $transactionId,
        ];
        broadcast(new DeviceDataReceived($device_data));
        
        return response()->json([
            'status' => 'pending',
            'message' => 'Command sent to device. Waiting for acknowledgment...',
            'transaction_id' => $transactionId,
        ]);
    }
    
    public function saveRodi(Request $request){
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'rodi_type' => 'required|in:pipe,container',
            'ml_value' => 'nullable|numeric'
        ]);
        
        $machine = Machine::findOrFail($request->machine_id);
        
        // Generate unique transaction ID for ECHO tracking
        $transactionId = PendingDeviceCommand::generateTransactionId();
        
        // Create pending command (will save to DB only after ESP32 ECHO)
        $pendingCommand = PendingDeviceCommand::create([
            'mac_id' => $machine->mac_id,
            'command' => 'save_rodi_setup',
            'payload' => [
                'machine_id' => $request->machine_id,
                'rodi_type' => $request->rodi_type,
                'ml_value' => $request->ml_value,
            ],
            'transaction_id' => $transactionId,
            'status' => 'pending',
            'sent_at' => now(),
        ]);
        
        // Send to ESP32 with transaction_id for ECHO
        $device_data = [
            'mac_id' => $machine->mac_id,
            'command' => 'save_rodi_setup',
            'rodi_type' => $request->rodi_type,
            'ml_value' => $request->ml_value,
            'transaction_id' => $transactionId,
        ];
        broadcast(new DeviceDataReceived($device_data));
        
        return response()->json([
            'status' => 'pending',
            'message' => 'Command sent to device. Waiting for acknowledgment...',
            'transaction_id' => $transactionId,
        ]);
    }
    
    public function saveOthers(Request $request){
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'clarity_test' => 'required|in:ON,OFF',
            'tem_test' => 'required|in:ON,OFF',
            'alarm' => 'required|in:ON,OFF'
        ]);
        
        $machine = Machine::findOrFail($request->machine_id);
        
        // Generate unique transaction ID for ECHO tracking
        $transactionId = PendingDeviceCommand::generateTransactionId();
        
        // Create pending command (will save to DB only after ESP32 ECHO)
        $pendingCommand = PendingDeviceCommand::create([
            'mac_id' => $machine->mac_id,
            'command' => 'save_other_settings',
            'payload' => [
                'machine_id' => $request->machine_id,
                'clarity_test' => $request->clarity_test,
                'tem_test' => $request->tem_test,
                'alarm' => $request->alarm,
            ],
            'transaction_id' => $transactionId,
            'status' => 'pending',
            'sent_at' => now(),
        ]);
        
        // Send to ESP32 with transaction_id for ECHO
        $device_data = [
            'mac_id' => $machine->mac_id,
            'command' => 'save_other_settings',
            'clarity_test' => $request->clarity_test,
            'tem_test' => $request->tem_test,
            'alarm' => $request->alarm,
            'transaction_id' => $transactionId,
        ];
        broadcast(new DeviceDataReceived($device_data));
        
        return response()->json([
            'status' => 'pending',
            'message' => 'Command sent to device. Waiting for acknowledgment...',
            'transaction_id' => $transactionId,
        ]);
    }
    
}
