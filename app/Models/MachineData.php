<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MachineData extends Model
{
    protected $table = 'machine_data';

    protected $fillable = [
        'machine_id',
        'test_name',
        'brand_id',
        'reagent_a_location_id',
        'reagent_b_location_id',
        'reagent_c_location_id',
        'reagent_d_location_id',
        'reagent_e_location_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function machine()
    {
        return $this->belongsTo(\App\Models\Machine::class)->withDefault();
    }

    public function brand()
    {
        return $this->belongsTo(\App\Models\Brand::class)->withDefault();
    }

    public function reagentALocation()
    {
        return $this->belongsTo(\App\Models\Location::class, 'reagent_a_location_id')->withDefault();
    }

    public function reagentBLocation()
    {
        return $this->belongsTo(\App\Models\Location::class, 'reagent_b_location_id')->withDefault();
    }

    public function reagentCLocation()
    {
        return $this->belongsTo(\App\Models\Location::class, 'reagent_c_location_id')->withDefault();
    }

    public function reagentDLocation()
    {
        return $this->belongsTo(\App\Models\Location::class, 'reagent_d_location_id')->withDefault();
    }

    public function reagentELocation()
    {
        return $this->belongsTo(\App\Models\Location::class, 'reagent_e_location_id')->withDefault();
    }
}
