<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MachineOther extends Model
{
    protected $fillable = ['machine_id', 'clarity_test', 'tem_test', 'alarm'];
}
