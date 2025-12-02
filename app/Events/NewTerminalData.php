<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class NewTerminalData implements ShouldBroadcast
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function broadcastOn()
    {
        // Use device-specific channel if mac_id provided, otherwise use terminal channel
        if (isset($this->data['mac_id'])) {
            $mac = $this->data['mac_id'];
            $safeMac = str_replace(':', '', $mac);
            $channel = 'device.' . $safeMac;
        } else {
            $channel = 'terminal';
        }

        return new Channel($channel);
    }

    public function broadcastAs()
    {
        return 'device.command';
    }
}
