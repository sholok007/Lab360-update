<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendingDeviceCommand extends Model
{
    use HasFactory;

    protected $fillable = [
        'mac_id',
        'command',
        'payload',
        'transaction_id',
        'status',
        'sent_at',
        'acknowledged_at',
        'error_message',
        'retry_count',
        'attempts',
        'last_attempt_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'payload' => 'array',
        'last_attempt_at' => 'datetime',
    ];

    /**
     * Generate a unique transaction ID
     */
    public static function generateTransactionId(): string
    {
        return uniqid('txn_', true) . '_' . time();
    }

    /**
     * Mark command as acknowledged
     */
    public function markAsAcknowledged(): void
    {
        $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Mark command as failed
     */
    public function markAsFailed(string $errorMessage = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Mark command as timeout
     */
    public function markAsTimeout(): void
    {
        $this->update([
            'status' => 'timeout',
        ]);
    }

    /**
     * Increment retry count
     */
    public function incrementRetry(): void
    {
        $this->increment('retry_count');
    }

    /**
     * Scope for pending commands
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for commands by MAC ID
     */
    public function scopeForDevice($query, string $macId)
    {
        return $query->where('mac_id', $macId);
    }

    /**
     * Scope for timeout commands (pending for more than X minutes)
     */
    public function scopeTimedOut($query, int $minutes = 5)
    {
        return $query->where('status', 'pending')
                    ->where('sent_at', '<', now()->subMinutes($minutes));
    }
}
