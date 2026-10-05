<?php

namespace Modules\Course\app\Helper;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;
use Modules\Course\app\Models\CourseCategory;

class CourseCategoryHelper {
    public static function getAll(?string $lang = null): Collection {
        $lang ??= getSessionLanguage();

        return Cache::rememberForever("course_categories_{$lang}", function () use ($lang) {
            return CourseCategory::select('course_categories.*')
                ->selectRaw('t.name as translation_name')
                ->leftJoin('course_category_translations as t', function ($q) use ($lang) {
                    $q->on('t.course_category_id', '=', 'course_categories.id')
                        ->where('t.lang_code', $lang);
                })
                ->where('status', 1)
                ->get();
        });
    }

    public static function getTree(?string $lang = null): Collection {
        $lang ??= getSessionLanguage();

        return Cache::rememberForever("course_category_tree_{$lang}", function () use ($lang) {
            return CourseCategory::with([
                'translation',
                'subCategories' => function ($query) {
                    $query->where('status', 1)->with('translation');
                },
            ])
                ->whereNull('parent_id')
                ->where('status', 1)
                ->orderByDesc('id')
                ->get();
        });
    }

    public static function getRoots(?string $lang = null): Collection {
        return static::getAll($lang)->whereNull('parent_id')->values();
    }

    public static function find(int|string|null $id, ?string $lang = null): ?CourseCategory {
        if (!$id) {
            return null;
        }

        return static::getAll($lang)->firstWhere('id', (int) $id);
    }

    public static function findBySlug(?string $slug, ?string $lang = null): ?CourseCategory {
        if (!$slug) {
            return null;
        }

        return static::getAll($lang)->firstWhere('slug', $slug);
    }

    public static function getSubCategoriesByParentSlug(?string $slug, ?string $lang = null): Collection {
        $parent = static::findBySlug($slug, $lang);

        if (!$parent) {
            return collect();
        }

        return static::getAll($lang)->where('parent_id', $parent->id)->values();
    }

    public static function getTrendingCategories(?string $lang = null): Collection {
        $lang ??= getSessionLanguage();

        return Cache::rememberForever("trending_categories_{$lang}", function () use ($lang) {
            return CourseCategory::select('course_categories.*')
                ->selectRaw('(
                    SELECT COUNT(*) FROM course_categories as sub
                    INNER JOIN courses ON courses.category_id = sub.id
                    WHERE sub.parent_id = course_categories.id AND courses.status = "active"
                ) as total_courses')
                ->selectRaw('t.name as translation_name')
                ->leftJoin('course_category_translations as t', function ($q) use ($lang) {
                    $q->on('t.course_category_id', '=', 'course_categories.id')
                        ->where('t.lang_code', $lang);
                })
                ->whereNull('parent_id')
                ->where('status', 1)
                ->where('show_at_trending', 1)
                ->get();
        });
    }

    public static function getTrendingCategoriesWithCounts(?string $lang = null): Collection {
        $lang ??= getSessionLanguage();

        return Cache::rememberForever("trending_categories_with_counts_{$lang}", function () {
            return CourseCategory::with([
                'translation:id,name,course_category_id',
                'subCategories' => function ($query) {
                    $query->where('status', 1)->withCount([
                        'courses' => function ($courseQuery) {
                            $courseQuery->where('status', 'active');
                        },
                    ]);
                },
            ])->withCount([
                'subCategories as active_sub_categories_count' => function ($query) {
                    $query->whereHas('courses', function ($courseQuery) {
                        $courseQuery->where('status', 'active');
                    });
                },
            ])->whereNull('parent_id')
                ->where('status', 1)
                ->where('show_at_trending', 1)
                ->get();
        });
    }
}
