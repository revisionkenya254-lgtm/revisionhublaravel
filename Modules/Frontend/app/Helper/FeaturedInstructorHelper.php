<?php

namespace Modules\Frontend\app\Helper;

use Illuminate\Support\Facades\Cache;
use Modules\Frontend\app\Models\FeaturedInstructor;

class FeaturedInstructorHelper {
    public static function getAll($hours = 24) {
        $lang = getSessionLanguage();
        $cacheKey = "featured_instructor_{$lang}";

        return Cache::remember($cacheKey, now()->addHours($hours), function () use ($lang) {
            return FeaturedInstructor::selectRaw("featured_instructors.instructor_ids, featured_instructors.button_url, t.title as translation_title, t.sub_title as translation_sub_title, t.button_text as translation_button_text")
                ->leftJoin('featured_instructor_translations as t', function ($q) use ($lang) {
                    $q->on('t.featured_instructor_section_id', '=', 'featured_instructors.id')
                        ->where('t.lang_code', $lang);
                })->first();
        });
    }
}
