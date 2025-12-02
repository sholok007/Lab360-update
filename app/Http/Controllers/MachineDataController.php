<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MachineData;
use App\Models\Machine;
use App\Models\Test;
use App\Models\Location;

class MachineDataController extends Controller{


    public function index()
    {
        $machine = Machine::first(); 
        $tests = Test::all();
        $locations = Location::all();

        $machineData = MachineData::with([
            'brand',
            'reagentALocation',
            'reagentBLocation',
            'reagentCLocation',
            'reagentDLocation',
            'reagentELocation'
        ])->get();

        return view('machines.setup', compact('machine', 'tests', 'locations', 'machineData'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'chip_id' => 'required',
            'test_name' => 'required',
            'brand_id' => 'required',
            'reagent_a_location_id' => 'nullable',
            'reagent_b_location_id' => 'nullable',
            'reagent_c_location_id' => 'nullable',
            'reagent_d_location_id' => 'nullable',
            'reagent_e_location_id' => 'nullable',
        ]);

        $machine = Machine::findOrFail($request->machine_id);
        $transactionId = \App\Models\PendingDeviceCommand::generateTransactionId();

        // Create pending command (will save to DB only after ESP32 ECHO)
        $pendingCommand = \App\Models\PendingDeviceCommand::create([
            'mac_id' => $machine->mac_id,
            'command' => 'save_machine_data',
            'payload' => [
                'machine_id' => $request->machine_id,
                'chip_id' => $request->chip_id,
                'test_name' => $request->test_name,
                'brand_id' => $request->brand_id,
                'reagent_a_location_id' => $request->reagent_a_location_id,
                'reagent_b_location_id' => $request->reagent_b_location_id,
                'reagent_c_location_id' => $request->reagent_c_location_id,
                'reagent_d_location_id' => $request->reagent_d_location_id,
                'reagent_e_location_id' => $request->reagent_e_location_id,
            ],
            'transaction_id' => $transactionId,
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        // Send to ESP32 with transaction_id for ECHO
        $device_data = [
            'mac_id' => $machine->mac_id,
            'command' => 'save_machine_data',
            'chip_id' => $request->chip_id,
            'test_name' => $request->test_name,
            'brand_id' => $request->brand_id,
            'reagent_a_location_id' => $request->reagent_a_location_id,
            'reagent_b_location_id' => $request->reagent_b_location_id,
            'reagent_c_location_id' => $request->reagent_c_location_id,
            'reagent_d_location_id' => $request->reagent_d_location_id,
            'reagent_e_location_id' => $request->reagent_e_location_id,
            'transaction_id' => $transactionId,
        ];
        broadcast(new \App\Events\DeviceDataReceived($device_data));

        return response()->json([
            'status' => 'pending',
            'message' => 'Command sent to device. Waiting for acknowledgment...',
            'transaction_id' => $transactionId,
        ]);
    }


    

}
