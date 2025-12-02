<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Test;
use App\Models\Brand;
use App\Models\Location;
use App\Models\Machine;
use App\Models\MachineData;

class ESPSetupController extends Controller
{
    // Show reagent setup & machine data list
    public function reagentSetup($machineId)
    {
        $user = auth()->user();

        // Ensure machine belongs to logged-in user
        $machine = Machine::where('id', $machineId)
            ->where('user_id', $user->id)
            ->first();

        if (!$machine) {
            return abort(404, 'Machine not found for this user.');
        }

        $tests = Test::all();
        $locations = Location::all();

        $machineData = MachineData::with([
            'brand',
            'reagentALocation',
            'reagentBLocation',
            'reagentCLocation',
            'reagentDLocation',
            'reagentELocation'
        ])
            ->where('machine_id', $machine->id ?? 0)
            ->latest()
            ->get();

        return view('machines.setup', compact('machine', 'tests', 'locations', 'machineData'));
    }

    // Save reagent locations
    public function saveReagentLocation(Request $request)
    {
        $data = $request->validate([
            'machine_id' => 'required|integer|exists:machines,id',
            'test_name'  => 'required|string|max:255',
            'brand_id'   => 'required|integer|exists:brands,id',
            'mappings'   => 'required|array|min:1',
            'device_data' => 'nullable|array',
        ]);

        $machine = Machine::findOrFail($data['machine_id']);

        // Generate transaction ID for ECHO
        $transactionId = \App\Models\PendingDeviceCommand::generateTransactionId();

        // Create pending command (do not save to MachineData yet)
        $pendingCommand = \App\Models\PendingDeviceCommand::create([
            'mac_id' => $machine->mac_id,
            'command' => 'reagent_location_update',
            'payload' => [
                'machine_id' => $data['machine_id'],
                'test_name' => $data['test_name'],
                'brand_id' => $data['brand_id'],
                'mappings' => $data['mappings'],
                'device_data' => $data['device_data'] ?? null,
            ],
            'transaction_id' => $transactionId,
            'status' => 'pending',
            'sent_at' => now(),
        ]);

        // Broadcast to ESP32 device (no double data nesting)
        if ($machine->mac_id) {
            $devicePayload = [
                'mac_id' => $machine->mac_id,
                'command' => 'reagent_location_update',
                'test_name' => $data['test_name'],
                'brand_id' => $data['brand_id'],
                'mappings' => $data['mappings'],
                'device_data' => $data['device_data'] ?? null,
                'transaction_id' => $transactionId,
                'timestamp' => now()->timestamp,
            ];
            event(new \App\Events\DeviceDataReceived($devicePayload));
        }

        return response()->json([
            'status' => 'pending',
            'message' => 'Command sent to device. Waiting for acknowledgment...',
            'transaction_id' => $transactionId,
        ]);
    }

    // Delete machine data
    public function deleteMachineData($id)
    {
        $machineData = MachineData::with(['brand', 'machine'])->find($id);

        if (!$machineData) {
            return response()->json([
                'status' => 'error',
                'message' => 'Record not found!'
            ], 404);
        }

        // Get machine for broadcasting
        $machine = $machineData->machine;

        // Prepare data to send to ESP32 before deleting
        $deletedData = [
            'test_name' => $machineData->test_name,
            'brand_name' => $machineData->brand->name ?? 'Unknown',
            'locations' => [
                'reagent_a' => $machineData->reagent_a_location_id,
                'reagent_b' => $machineData->reagent_b_location_id,
                'reagent_c' => $machineData->reagent_c_location_id,
                'reagent_d' => $machineData->reagent_d_location_id,
                'reagent_e' => $machineData->reagent_e_location_id,
            ]
        ];

        // Delete the record
        $machineData->delete();

        // Broadcast deletion to ESP32
        if ($machine && $machine->mac_id) {
            $devicePayload = [
                'mac_id' => $machine->mac_id,
                'command' => 'reagent_location_deleted',
                'deleted_data' => $deletedData,
                'timestamp' => now()->timestamp,
            ];

            event(new \App\Events\DeviceDataReceived($devicePayload));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Deleted successfully and notified device!'
        ]);
    }

    public function show($machineId){
        // Machine load
        $machine = Machine::findOrFail($machineId);

        $locations = Location::all();

        $machineData = MachineData::with([
            'brand',
            'reagentALocation',
            'reagentBLocation',
            'reagentCLocation',
            'reagentDLocation',
            'reagentELocation'
        ])->where('machine_id', $machine->id)->get();

        // Blade view return
        return view('machines.show', compact('machine', 'locations', 'machineData'));
    }


    public function getReagents($brandId, $machineId) {
        $reagents = \App\Models\Reagent::where('brand_id', $brandId)->get();

        $usedLocations = MachineData::where('machine_id', $machineId)
            ->where('brand_id', $brandId)
            ->get([
                'reagent_a_location_id',
                'reagent_b_location_id',
                'reagent_c_location_id',
                'reagent_d_location_id',
                'reagent_e_location_id'
            ])
            ->pluck('reagent_a_location_id', 'reagent_b_location_id', 'reagent_c_location_id', 'reagent_d_location_id', 'reagent_e_location_id')
            ->flatten()
            ->filter() 
            ->unique()
            ->values()
            ->all();

        return response()->json([
            'reagents' => $reagents,
            'usedLocations' => $usedLocations
        ]);
    }
}
