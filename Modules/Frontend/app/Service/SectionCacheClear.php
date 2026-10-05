<?php

namespace Modules\Frontend\app\Service;

use Illuminate\Support\Facades\Cache;

class SectionCacheClear {
    public static function clear($theme_name): void {
        try {
            if (function_exists('allLanguages')) {
                $langs = allLanguages()?->pluck('code')->filter()->all() ?? [];
                foreach ($langs as $lang) {
                    Cache::forget("{$theme_name}_home_sections_{$lang}");
                }
                return;
            }
        } catch (\Throwable $e) {
            // ignore and fallback to current session language
        }
    }

}
