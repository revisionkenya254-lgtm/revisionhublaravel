<?php

namespace Modules\Course\app\Service;

use App\Services\CatalogCacheClear;
use Illuminate\Support\Facades\Cache;

class CourseCategoryCacheClear {
    public static function clear(): void {
        $languages = [config('app.locale')];

        try {
            if (function_exists('allLanguages')) {
                $languages = array_merge($languages, allLanguages()?->pluck('code')->filter()->all() ?? []);
            }
        } catch (\Throwable $e) {
            // Ignore and fall back to known locales.
        }

        if (function_exists('getSessionLanguage')) {
            $languages[] = getSessionLanguage();
        }

        foreach (array_unique(array_filter($languages)) as $lang) {
            Cache::forget("course_categories_{$lang}");
            Cache::forget("course_category_tree_{$lang}");
            Cache::forget("trending_categories_{$lang}");
            Cache::forget("trending_categories_with_counts_{$lang}");
        }

        app(\App\Services\MenuCacheService::class)->clearMenuCache();
        CatalogCacheClear::clear();
    }
}
