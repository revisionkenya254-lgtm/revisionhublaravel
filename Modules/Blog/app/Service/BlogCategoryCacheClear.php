<?php

namespace Modules\Blog\app\Service;

use Illuminate\Support\Facades\Cache;

class BlogCategoryCacheClear
{
    public static function clear(): void
    {
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
            Cache::forget("blog_categories_active_{$lang}");
            Cache::forget("blog_categories_all_{$lang}");
        }
    }
}
