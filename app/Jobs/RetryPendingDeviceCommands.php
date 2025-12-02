<?php

namespace App\Jobs;

use App\Models\PendingDeviceCommand;
use App\Events\DeviceDataReceived;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryPendingDeviceCommands implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $pendingCommands = PendingDeviceCommand::where('status', 'pending')
            ->where('attempts', '<', 3)
            ->get();

        foreach ($pendingCommands as $command) {
            // Re-broadcast the command to the device
            $payload = $command->payload;
            $payload['mac_id'] = $command->mac_id;
            $payload['command'] = $command->command;
            $payload['transaction_id'] = $command->transaction_id;
            broadcast(new DeviceDataReceived($payload));

            $command->attempts += 1;
            $command->last_attempt_at = now();
            $command->save();

            Log::info('🔁 Retried pending device command', [
                'transaction_id' => $command->transaction_id,
                'attempts' => $command->attempts,
            ]);

            // If this was the 3rd attempt, mark as failed
            if ($command->attempts >= 3) {
                $command->markAsFailed('Device not available after 3 attempts');
                // Optionally, notify frontend here
            }
        }
    }
}
