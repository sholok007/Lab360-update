<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});



// Machine channel - public for all users
Broadcast::channel('machine.{macId}', function ($macId) {
    // Return true to allow everyone
    return true;
});

// Device channel - for sending commands to ESP32
Broadcast::channel('device.{macId}', function ($macId) {
    // Return true to allow everyone
    return true;
});