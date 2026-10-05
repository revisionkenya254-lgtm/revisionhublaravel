<?php

namespace Modules\Menubuilder\app\Service;

use Illuminate\Support\Facades\Cache;

class MenuCacheClear {
    /**
     * Clear cached menu entries for all languages (or current language fallback).
     */
    public static function clear($slug): void {
        try {
            if (function_exists('allLanguages')) {
                $langs = allLanguages()?->pluck('code')->filter()->all() ?? [];
                foreach ($langs as $lang) {
                    Cache::forget("menu_{$slug}_{$lang}");
                }
                return;
            }
        } catch (\Throwable $e) {
            // ignore and fallback to current session language
        }
    }
}
