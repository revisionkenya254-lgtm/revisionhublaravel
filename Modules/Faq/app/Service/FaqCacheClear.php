<?php

namespace Modules\Faq\app\Service;

use Illuminate\Support\Facades\Cache;

class FaqCacheClear {
    /**
     * Clear cached FAQ entries for all languages (or current language fallback).
     */
    public static function clear(): void {
        try {
            if (function_exists('allLanguages')) {
                $langs = allLanguages()?->pluck('code')->filter()->all() ?? [];
                foreach ($langs as $lang) {
                    Cache::forget("faqs_{$lang}");
                }
                return;
            }
        } catch (\Throwable $e) {
            // ignore and fallback to current session language
        }
    }
}
