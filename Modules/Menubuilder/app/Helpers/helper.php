<?php

use Modules\Menubuilder\app\Models\Menus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

if (! function_exists('menu_get_by_slug')) {
    function menu_get_by_slug($slug) {
        if (
            !Schema::hasTable('menus') ||
            !Schema::hasTable('menu_items') ||
            !Schema::hasTable('menu_item_translations')
        ) {
            return null;
        }

        $lang = getSessionLanguage();
        $cacheKey = "menu_{$slug}_{$lang}";

        return Cache::remember($cacheKey, now()->addHours(1), function () use ($lang, $slug) {
            try {
                $menu = Menus::select('id', 'slug', 'name')->where('slug', $slug)->first();
                if (! $menu) {
                    return null;
                }

                // Get top-level items with translation label via join
                $parentItems = DB::table('menu_items as mi')
                    ->select('mi.*', DB::raw('t.label as translation_label'))
                    ->leftJoin('menu_item_translations as t', function ($join) use ($lang) {
                        $join->on('t.menu_item_id', '=', 'mi.id')
                             ->where('t.lang_code', $lang);
                    })
                    ->where('mi.menu_id', $menu->id)
                    ->where('mi.parent_id', 0)
                    ->orderBy('mi.sort', 'ASC')
                    ->get();

                $parentIds = $parentItems->pluck('id')->all();

                // Get one-level children with translation label via join
                $children = collect();
                if (! empty($parentIds)) {
                    $children = DB::table('menu_items as mi')
                        ->select('mi.*', DB::raw('t.label as translation_label'))
                        ->leftJoin('menu_item_translations as t', function ($join) use ($lang) {
                            $join->on('t.menu_item_id', '=', 'mi.id')
                                 ->where('t.lang_code', $lang);
                        })
                        ->whereIn('mi.parent_id', $parentIds)
                        ->orderBy('mi.sort', 'ASC')
                        ->get();
                }

                $childrenByParent = $children->groupBy('parent_id');

                // Attach children collection to each parent item
                $parentItems = $parentItems->map(function ($p) use ($childrenByParent) {
                    $p->children = $childrenByParent[$p->id] ?? collect();
                    return $p;
                });

                // Attach to model for compatibility
                $menu->menuItems = $parentItems;
                return $menu;
            } catch (\Throwable $exception) {
                return null;
            }
        });
    }
}
if (!function_exists('currectUrlWithQuery')) {
    function currectUrlWithQuery($code) {
        $currentUrlWithQuery = request()->fullUrl();

        // Parse the query string
        $parsedQuery = parse_url($currentUrlWithQuery, PHP_URL_QUERY);

        // Check if the 'code' parameter already exists
        $codeExists = false;
        if ($parsedQuery) {
            parse_str($parsedQuery, $queryArray);
            $codeExists = isset($queryArray['code']);
        }

        if ($codeExists) {
            $updatedUrlWithQuery = preg_replace('/(\?|&)code=[^&]*/', '$1code=' . $code, $currentUrlWithQuery);
        } else {
            $updatedUrlWithQuery = $currentUrlWithQuery . ($parsedQuery ? '&' : '?') . http_build_query(['code' => $code]);
        }
        return $updatedUrlWithQuery;
    }
}
