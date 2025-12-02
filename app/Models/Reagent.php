<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reagent extends Model
{
 
        protected $fillable = ['name'];

        public function brands()
        {
        return $this->belongsToMany(Brand::class, 'brand_reagent');
        }
    
}
