<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $fillable = ['name', 'test_id'];

    public function test()
    {
        return $this->belongsTo(Test::class);
    }

    /*public function reagents()
    {
        return $this->belongsToMany(Reagent::class, 'brand_reagent');
    }*/

    public function reagents()
    {
        return $this->belongsToMany(\App\Models\Reagent::class, 'brand_reagent');
    }
}
