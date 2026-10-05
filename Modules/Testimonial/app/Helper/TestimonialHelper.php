<?php

namespace Modules\Testimonial\app\Helper;

use Illuminate\Support\Facades\Cache;
use Modules\Testimonial\app\Models\Testimonial;

class TestimonialHelper {
    public static function getAll($hours = 24) {
        $lang = getSessionLanguage();
        $cacheKey = "testimonials_{$lang}";

        return Cache::remember($cacheKey, now()->addHours($hours), function () use ($lang) {
            return Testimonial::selectRaw("testimonials.image, testimonials.rating, t.name as translation_name, t.comment as translation_comment, t.designation as translation_designation")
                ->leftJoin('testimonial_translations as t', function ($q) use ($lang) {
                    $q->on('t.testimonial_id', '=', 'testimonials.id')
                        ->where('t.lang_code', $lang);
                })->where('status', 1)->get();
        });
    }
}
