<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PendingDeviceCommand;

class CleanupPendingCommands extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:cleanup-pending {--timeout=5 : Minutes before marking as timeout}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark old pending device commands as timeout';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeoutMinutes = $this->option('timeout');
        
        $this->info("Checking for pending commands older than {$timeoutMinutes} minutes...");

        // Find timed out commands
        $timedOutCommands = PendingDeviceCommand::timedOut($timeoutMinutes)->get();

        if ($timedOutCommands->isEmpty()) {
            $this->info('✅ No timed out commands found.');
            return 0;
        }

        $count = $timedOutCommands->count();
        $this->warn("⚠️  Found {$count} timed out commands:");

        $this->table(
            ['ID', 'MAC ID', 'Command', 'Transaction ID', 'Sent At'],
            $timedOutCommands->map(function ($cmd) {
                return [
                    $cmd->id,
                    $cmd->mac_id,
                    $cmd->command,
                    $cmd->transaction_id,
                    $cmd->sent_at->format('Y-m-d H:i:s'),
                ];
            })
        );

        if ($this->confirm('Mark these commands as timeout?', true)) {
            foreach ($timedOutCommands as $command) {
                $command->markAsTimeout();
            }
            $this->info("✅ {$count} commands marked as timeout.");
        } else {
            $this->info('Operation cancelled.');
        }

        return 0;
    }
}
