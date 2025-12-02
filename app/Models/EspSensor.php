<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EspSensor extends Model
{
    use HasFactory;

    protected $table = 'esp_sensor'; // your table name

    protected $fillable = [
        'test_name', 
        'brand', 
        'color_r', 
        'color_g', 
        'color_b', 
        'tem', 
        'sen_1', 
        'sen_2', 
        'sen_3', 
        'sen_4', 
        'sen_5', 
        'sen_6'
    ];

    public $timestamps = false; // Because you already use 'recorded_at'
}