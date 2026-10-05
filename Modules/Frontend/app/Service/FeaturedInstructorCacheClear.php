<?php

namespace Modules\Frontend\app\Service;

use Illuminate\Support\Facades\Cache;

class FeaturedInstructorCacheClear {
    public static function clear(): void {
        try {
            if (function_exists('allLanguages')) {
                $langs = allLanguages()?->pluck('code')->filter()->all() ?? [];
                foreach ($langs as $lang) {
                    Cache::forget("featured_instructor_{$lang}");
                }
                return;
            }
        } catch (\Throwable $e) {
            // ignore and fallback to current session language
        }
    }

}
