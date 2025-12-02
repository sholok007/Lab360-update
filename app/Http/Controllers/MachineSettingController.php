<?php

namespace App\Http\Controllers;

use App\Models\MachineDrain;
use App\Models\MachineRodi;
use App\Models\MachineOther;
use Illuminate\Http\Request;

class MachineSettingController extends Controller
{

    public function send(Request $request)
{
    try {
        // Validate input
        $request->validate([
            'mac_id' => 'required',
            'command' => 'required'
        ]);

        // 1️⃣ Save to database (device_command_logs table)
        $log = \DB::table('device_command_logs')->insert([
            'mac_id' => $request->mac_id,
            'command' => $request->command,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 2️⃣ Send command through WebSocket / HTTP (if needed later)
        // For now just return success

        return response()->json([
            'status' => 'success',
            'message' => 'Command saved!'
        ]);

    } catch (\Exception $e) {
        \Log::error("Send Command Error: ".$e->getMessage());
        return response()->json([
            'status' => 'error',
            'message' => 'Server failed',
            'error' => $e->getMessage()
        ], 500);
    }
}


    // 🌧️ Save Drain
  public function saveDrain(Request $request)
{
    $request->validate([
        'machine_id' => 'required|exists:machines,id',
        'type' => 'required|string',
        'ml_value' => 'nullable|numeric'
    ]);

    try {
        MachineDrain::updateOrCreate(
            ['machine_id' => $request->machine_id],
            [
                'drain_type' => $request->type,
                'ml_value' => $request->ml_value
            ]
        );

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        \Log::error('Save Drain Error: '.$e->getMessage());
        return response()->json(['success'=>false,'error'=>$e->getMessage()], 500);
    }
}

    // 💧 Save RODI
    public function saveRodi(Request $request)
    {
        $request->validate([
            'machine_id' => 'required',
            'type' => 'required',
        ]);

        MachineRodi::updateOrCreate(
            ['machine_id' => $request->machine_id],
            [
                'rodi_type' => $request->type,
                'ml_value' => $request->ml_value
            ]
        );

        return response()->json(['success' => true]);
    }

    // 🔘 Save Others
    public function saveOthers(Request $request)
    {
        MachineOther::updateOrCreate(
            ['machine_id' => $request->machine_id],
            [
                'clarity_test' => $request->clarity,
                'tem_test' => $request->tem,
                'alarm' => $request->alarm,
            ]
        );

        return response()->json(['success' => true]);
    }
}

