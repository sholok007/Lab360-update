<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MachineDrain extends Model
{
    protected $fillable = ['machine_id', 'drain_type', 'ml_value'];
}
