<?php

namespace Modules\FooterSetting\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FooterSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    /**
     * Booted the model.
    */
    protected static function booted() {
        static::saved(fn() => Cache::forget('footer_settings'));
        // `deleted` fires when the model is deleted
        static::deleted(fn() => Cache::forget('footer_settings'));
    }
    
  
}
