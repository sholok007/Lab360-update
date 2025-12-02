<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class DeviceAcknowledged implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $payload;

    public function __construct($payload)
    {
        $this->payload = $payload;
    }

    public function broadcastOn()
    {
        $mac = isset($this->payload['mac_id']) ? $this->payload['mac_id'] : '';
        $safeMac = str_replace(':', '', $mac);
        return new Channel('machine.' . $safeMac);
    }

    public function broadcastAs()
    {
        return 'device.acknowledged';
    }

    public function broadcastWith()
    {
        return $this->payload;
    }
}
