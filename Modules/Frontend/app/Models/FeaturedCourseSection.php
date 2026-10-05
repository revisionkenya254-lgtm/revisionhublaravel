<?php

namespace Modules\Frontend\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeaturedCourseSection extends Model {
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $guarded = [];

    protected static function booted() {
        static::saved(fn() => Cache::forget('featured_course_sections'));
        // `deleted` fires when the model is deleted
        static::deleted(fn() => Cache::forget('featured_course_sections'));
    }
}
