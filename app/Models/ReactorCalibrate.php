<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReactorCalibrate extends Model
{
    use HasFactory;

    protected $table = 'reactor_calibrates';

    protected $fillable = [
        'machine_id',
        'value',
    ];

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }
}
