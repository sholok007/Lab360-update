<?php

namespace App\Http\Controllers;

use App\Events\DeviceDataReceived;
use Illuminate\Http\Request;


class DeviceController extends Controller
{
    public function sendData(Request $request)
    {
        $data = [
            'mac_id' => $request->mac_id,
            'command' => $request->command,
            'timestamp' => now()->toDateTimeString(),
        ];

        // Broadcast event to WebSocket channel
        event(new DeviceDataReceived($data));

        return response()->json(['status' => 'success', 'data' => $data]);
    }

    // Optional test method
    public function testBroadcast()
    {
        event(new DeviceDataReceived([
            'mac_id' => 'TEST12345',
            'command' => 'ping',
            'timestamp' => now()->toDateTimeString(),
        ]));

        return response()->json(['status' => 'Broadcast sent to TEST12345']);
    }
}
