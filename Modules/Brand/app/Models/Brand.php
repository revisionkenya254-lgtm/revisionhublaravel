<?php

namespace Modules\Brand\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Brand extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = ['id'];

    /**
     * Booted the model.
    */
    protected static function booted() {
        static::saved(fn() => Cache::forget('brands'));
        // `deleted` fires when the model is deleted
        static::deleted(fn() => Cache::forget('brands'));
    }
    
}
