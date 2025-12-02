<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
    protected $fillable = [
        'user_id', 'machine_name', 'auth_code', 'mac_id', 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

