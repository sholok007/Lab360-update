<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Machine;
use App\Models\Test;
use App\Models\Brand;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use App\Events\DeviceDataReceived;

class MachineController extends Controller
{
    // List machines
    public function index()
    {
        $authUser = Auth::user();

        if ($authUser->role === 'Admin') {
            $machines = Machine::whereNotNull('mac_id')->paginate(10);
        } else {
            $machines = Machine::where('user_id', $authUser->id)
                                ->whereNotNull('mac_id')
                                ->paginate(10);
        }

        return view('machines.index', compact('machines'));
    }


    public function sendToDevice(Request $request){
         $machine = Machine::where('mac_id', $request->mac_id)->first();
    if (!$machine) return response()->json(['status' => 'error', 'message' => 'Machine not found'], 404);

    // ডাটাবেসে save
    $data = \App\Models\MachineData::create([
        'machine_id' => $machine->id,
        'test_name' => $request->test_name,
        'brand_id' => $request->brand_id,
        'reagent_a_location_id' => $request->reagent_a ?? null,
        'reagent_b_location_id' => $request->reagent_b ?? null,
        'reagent_c_location_id' => $request->reagent_c ?? null,
        'reagent_d_location_id' => $request->reagent_d ?? null,
        'reagent_e_location_id' => $request->reagent_e ?? null,
    ]);

    // ব্রডকাস্ট ইভেন্ট
    event(new DeviceDataReceived([
        'mac_id' => $machine->mac_id,
        'status' => 'ok',
        'message' => 'Command sent successfully!',
        'data' => $data
    ]));

    return response()->json(['sent' => true]);
}

    // Store new machine
   public function store(Request $request){
        // Check duplicates manually
        $exists = Machine::where('user_id', Auth::id())
            ->where(function ($q) use ($request) {
                $q->where('machine_name', $request->machine_name)
                ->orWhere('auth_code', $request->auth_code);
            })
            ->exists();

        if ($exists) {
            // Duplicate exists, redirect back with session flag
            return redirect()->back()->with('duplicate', true);
        }

        // Create new machine
        Machine::create([
            'user_id' => Auth::id(),
            'machine_name' => $request->machine_name,
            'auth_code' => $request->auth_code,
            'mac_id' => null,
        ]);

        return redirect()->back()->with('showPopup', true);
    }


    // Update machine name only
    public function update(Request $request, $id)
    {
        $authUser = Auth::user();
        $machine = Machine::findOrFail($id);

        if ($authUser->role !== 'Admin' && $machine->user_id !== $authUser->id) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate(['machine_name' => 'required|string|max:255']);

        $machine->update(['machine_name' => $request->machine_name]);

        return redirect()->back()->with('success', 'Machine name updated successfully!');
    }

    // Delete machine (Ajax)
    public function destroy($id)
    {
        $authUser = Auth::user();
        $machine = Machine::findOrFail($id);

        if ($authUser->role !== 'Admin' && $machine->user_id !== $authUser->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized action.'], 403);
        }

        $machine->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Machine deleted successfully!'
        ]);
    }

   

public function connectDevice(Request $request)
{
    $request->validate([
        'auth_code' => 'required|string',
        'mac_id' => 'required|string'
    ]);

    $machine = Machine::where('auth_code', $request->auth_code)->first();

    if (!$machine) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid auth code.'
        ], 400);
    }

    // If MAC is not set, register it now
    if (is_null($machine->mac_id)) {
        $machine->mac_id = $request->mac_id;
        $machine->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Machine registered successfully.',
            'data' => $machine
        ], 200);
    }

    // If MAC already matches
    if ($machine->mac_id === $request->mac_id) {
        return response()->json([
            'status' => 'success',
            'message' => 'Machine already registered.',
            'data' => $machine
        ], 200);
    }

    // If MAC mismatched
    return response()->json([
        'status' => 'error',
        'message' => 'MAC address does not match this machine.'
    ], 400);
}



    // Setup page
    public function setup($id)
    {
        $authUser = Auth::user();
        $locations = Location::all();

        if ($authUser->role === 'Admin') {
            $machine = Machine::findOrFail($id);
        } else {
            $machine = Machine::where('id', $id)
                              ->where('user_id', $authUser->id)
                              ->firstOrFail();
        }

        $tests = Test::all();
        return view('machines.setup', compact('machine', 'tests', 'locations'));
    }

    // Get brands by test
    public function getBrandsByTest($testId)
    {
        $brands = Brand::where('test_id', $testId)->get();
        return response()->json($brands);
    }

    // Get reagents by brand
    public function getReagentsByBrand($brandId)
    {
        $brand = \App\Models\Brand::with('reagents')->findOrFail($brandId);
        return response()->json([
            'reagents' => $brand->reagents->map(fn($r) => ['id' => $r->id, 'name' => $r->name])
        ]);
    }


   public function getReagents($brandId, $machineId){
    $reagents = Reagent::where('brand_id', $brandId)->get(['id', 'name']);

   
    $assignedLocations = collect(
        MachineData::where('machine_id', $machineId)
            ->get([
                'reagent_a_location_id',
                'reagent_b_location_id',
                'reagent_c_location_id',
                'reagent_d_location_id',
                'reagent_e_location_id'
            ])
            ->flatMap(function($row){
                return [
                    $row->reagent_a_location_id,
                    $row->reagent_b_location_id,
                    $row->reagent_c_location_id,
                    $row->reagent_d_location_id,
                    $row->reagent_e_location_id
                ];
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray()
    );

    return response()->json([
        'reagents' => $reagents,
        'assignedLocations' => $assignedLocations
    ]);
}



   public function saveReagentLocation(Request $request){
    $machineId = $request->machine_id;
    $brandId = $request->brand_id;
    $testName = $request->test_name;
    $mappings = $request->mappings; // [reagentId => locationId]

    
    $alreadyAssigned = MachineData::where('machine_id', $machineId)
        ->pluck('reagent_a_location_id')
        ->merge(MachineData::where('machine_id', $machineId)->pluck('reagent_b_location_id'))
        ->merge(MachineData::where('machine_id', $machineId)->pluck('reagent_c_location_id'))
        ->merge(MachineData::where('machine_id', $machineId)->pluck('reagent_d_location_id'))
        ->merge(MachineData::where('machine_id', $machineId)->pluck('reagent_e_location_id'))
        ->filter()
        ->unique()
        ->values()
        ->toArray();

    foreach ($mappings as $reagentId => $locationId) {
        if (in_array($locationId, $alreadyAssigned)) {
            // location আগেই assigned, skip
            continue;
        }

        // 🔹 create new row for this machine/test/brand
        $machineData = MachineData::create([
            'machine_id' => $machineId,
            'test_name' => $testName,
            'brand_id' => $brandId,
            'reagent_a_location_id' => $reagentId == 1 ? $locationId : null,
            'reagent_b_location_id' => $reagentId == 2 ? $locationId : null,
            'reagent_c_location_id' => $reagentId == 3 ? $locationId : null,
            'reagent_d_location_id' => $reagentId == 4 ? $locationId : null,
            'reagent_e_location_id' => $reagentId == 5 ? $locationId : null,
        ]);

        // add location to array so same request-e duplicate insert না হয়
        $alreadyAssigned[] = $locationId;
    }

    return response()->json([
        'status' => 'success',
        'message' => 'Reagents assigned successfully'
    ]);
}

public function getMachineData($mac_id)
{
    $machine = Machine::where('mac_id', $mac_id)->first();

    if (!$machine) {
        return response()->json([
            'status' => 'error',
            'message' => 'Machine not found.'
        ], 404);
    }

    $machineData = \App\Models\MachineData::where('machine_id', $machine->id)->get();

    return response()->json([
        'status' => 'success',
        'machine_name' => $machine->machine_name,
        'machine_id' => $machine->id,
        'data' => $machineData
    ]);
}

}

