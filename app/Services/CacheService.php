<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class CacheService
{
    /**
     * Main categories that should not appear in the topic strip menu.
     */
    private const HIDDEN_MAIN_CATEGORY_SLUGS = [
        'finance',
        'music',
        'marketing',
        'design',
        'personal-developemnt',
        'it-software',
        'business',
        'development',
    ];

    /**
     * Stable icon keys for mobile and other API clients.
     */
    private const MAIN_CATEGORY_ICONS = [
        'pre-primary' => 'child_care',
        'lower-primary' => 'school',
        'upper-primary' => 'school',
        'junior-school' => 'menu_book',
        'senior-school-cbc' => 'school',
        'high-school' => 'menu_book',
        'tvet' => 'construction',
        'certificate-courses' => 'workspace_premium',
        'diploma-courses' => 'description',
        'undergraduate' => 'account_balance',
        'professional-courses' => 'military_tech',
        'teacher-resources' => 'menu_book',
    ];

    /**
     * Cache TTL configurations (in seconds)
     */
    const TTL_SETTINGS = 3600; // 1 hour
    const TTL_STATIC_DATA = 86400; // 24 hours
    const TTL_CATEGORIES = 3600; // 1 hour
    const TTL_COURSE_LISTINGS = 900; // 15 minutes
    const TTL_COURSE_DETAILS = 1800; // 30 minutes
    const TTL_SEARCH_RESULTS = 300; // 5 minutes

    /**
     * Get settings with caching
     */
    public function getSettings(array $keys = [])
    {
        $sortedKeys = $keys;
        sort($sortedKeys);
        $cacheKey = 'settings_' . implode('_', $sortedKeys);
        
        return Cache::remember($cacheKey, self::TTL_SETTINGS, function () use ($keys) {
            $query = \Modules\GlobalSetting\app\Models\Setting::whereIn('key', $keys);
            return $query->pluck('value', 'key')->toArray();
        });
    }

    /**
     * Get all settings with caching
     */
    public function getAllSettings()
    {
        return Cache::remember('all_settings', self::TTL_SETTINGS, function () {
            return \Modules\GlobalSetting\app\Models\Setting::all()->toArray();
        });
    }

    /**
     * Clear settings-related cache keys.
     */
    public function clearSettingsCache(): void
    {
        Cache::forget('setting');
        Cache::forget('all_settings');
    }

    /**
     * Get main categories with caching
     */
    public function getMainCategories($languageCode = 'en', $limit = -1)
    {
        $cacheKey = "main_categories_v6_{$languageCode}_{$limit}";
        
        return Cache::remember($cacheKey, self::TTL_CATEGORIES, function () use ($languageCode, $limit) {
            $categories = collect(app(\App\Services\MenuCacheService::class)->getCategoryLinks($languageCode))
                ->reject(fn ($category) => in_array((string) $category['slug'], self::HIDDEN_MAIN_CATEGORY_SLUGS, true))
                ->when($limit >= 0, fn ($items) => $items->take($limit))
                ->map(fn ($category) => [
                    'slug' => (string) $category['slug'],
                    'name' => (string) $category['label'],
                    'icon' => self::MAIN_CATEGORY_ICONS[(string) $category['slug']] ?? 'school',
                    'show_at_trending' => false,
                ])
                ->values();

            return $categories->toArray();
        });
    }

    /**
     * Get sub categories with caching
     */
    public function getSubCategories($parentSlug, $languageCode = 'en', $limit = -1)
    {
        $cacheKey = "sub_categories_v3_{$parentSlug}_{$languageCode}_{$limit}";
        
        return Cache::remember($cacheKey, self::TTL_CATEGORIES, function () use ($parentSlug, $languageCode, $limit) {
            $categories = collect(app(\App\Services\MenuCacheService::class)->getSubCategoriesForMenu($parentSlug, $languageCode))
                ->when($limit >= 0, fn ($items) => $items->take($limit))
                ->map(function (array $category) {
                    return [
                        'slug' => (string) ($category['slug'] ?? ''),
                        'name' => (string) ($category['translation_name'] ?? $category['name'] ?? ''),
                        'translation_name' => (string) ($category['translation_name'] ?? $category['name'] ?? ''),
                        'children' => $category['children'] ?? [],
                        'href' => (string) ($category['href'] ?? ''),
                        'icon' => (string) ($category['icon'] ?? ''),
                    ];
                })
                ->values();

            return $categories->toArray();
        });
    }

    /**
     * Get popular courses with caching
     */
    public function getPopularCourses($limit = 2, $filters = [])
    {
        $cacheKey = "popular_courses_{$limit}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, self::TTL_COURSE_LISTINGS, function () use ($limit, $filters) {
            $query = \App\Models\Course::select('slug', 'title', 'instructor_id', 'thumbnail', 'price', 'discount')
                ->active()
                ->with(['instructor:id,name,image'])
                ->whereHas('category.parentCategory', fn($q) => $q->where('status', 1))
                ->whereHas('category', fn($q) => $q->where('status', 1))
                ->withCount(['reviews as average_rating' => function ($q) {
                    $q->select(\Illuminate\Support\Facades\DB::raw('coalesce(avg(rating), 0) as average_rating'))->where('status', 1);
                }, 'enrollments'])
                ->orderByDesc('enrollments_count')
                ->orderByDesc('average_rating')
                ->take($limit);

            // Apply additional filters if provided
            if (!empty($filters['category'])) {
                $query->whereHas('category', fn($q) => $q->where('slug', $filters['category']));
            }

            return $query->get()->toArray();
        });
    }

    /**
     * Get fresh courses with caching
     */
    public function getFreshCourses($limit = 2, $filters = [])
    {
        $cacheKey = "fresh_courses_{$limit}_" . md5(json_encode($filters));
        
        return Cache::remember($cacheKey, self::TTL_COURSE_LISTINGS, function () use ($limit, $filters) {
            $query = \App\Models\Course::select('slug', 'title', 'instructor_id', 'thumbnail', 'price', 'discount')
                ->active()
                ->with(['instructor:id,name,image'])
                ->whereHas('category.parentCategory', fn($q) => $q->where('status', 1))
                ->whereHas('category', fn($q) => $q->where('status', 1))
                ->withCount(['reviews as average_rating' => function ($q) {
                    $q->select(\Illuminate\Support\Facades\DB::raw('coalesce(avg(rating), 0) as average_rating'))->where('status', 1);
                }, 'enrollments'])
                ->latest()
                ->take($limit);

            // Apply additional filters if provided
            if (!empty($filters['category'])) {
                $query->whereHas('category', fn($q) => $q->where('slug', $filters['category']));
            }

            return $query->get()->toArray();
        });
    }

    /**
     * Get course details with caching
     */
    public function getCourseDetails($slug, $userId = 0)
    {
        $cacheKey = "course_details_{$slug}_user_{$userId}";
        
        return Cache::remember($cacheKey, self::TTL_COURSE_DETAILS, function () use ($slug, $userId) {
            $course = \App\Models\Course::active()
                ->where('slug', $slug)
                ->select('id', 'instructor_id', 'demo_video_source', 'demo_video_storage', 'thumbnail', 'title', 'slug', 'price', 'discount', 'description', 'updated_at')
                ->with([
                    'instructor:id,name,image',
                    'chapters' => function ($query) {
                        $query->where('status', 'active')
                            ->select('id', 'course_id', 'title')
                            ->orderBy('order', 'asc')
                            ->with([
                                'chapterItems:id,chapter_id,type',
                                'chapterItems.quiz' => fn($q) => $q->select('id', 'chapter_item_id', 'title')->where('status', 'active'),
                                'chapterItems.lesson' => fn($q) => $q->select('id', 'chapter_item_id', 'title', 'file_path', 'storage', 'file_type', 'duration', 'is_free')->where('status', 'active'),
                            ]);
                    },
                    'languages:id,course_id,language_id' => ['language:id,name'],
                ])
                ->whereHas('category.parentCategory', fn($q) => $q->where('status', 1))
                ->whereHas('category', fn($q) => $q->where('status', 1))
                ->withCount([
                    'reviews as average_rating' => fn($q) => $q->select(\Illuminate\Support\Facades\DB::raw('coalesce(avg(rating), 0)'))->where('status', 1),
                    'reviews' => fn($q) => $q->where('status', 1),
                    'lessons', 'quizzes', 'enrollments',
                    'favoriteBy as is_wishlist' => function ($query) use ($userId) {
                        $query->where('user_id', $userId);
                    },
                ])
                ->first();

            return $course ? $course->toArray() : null;
        });
    }

    /**
     * Get static data (languages, currencies, countries) with caching
     */
    public function getLanguages()
    {
        return Cache::remember('languages', self::TTL_STATIC_DATA, function () {
            return \Modules\Language\app\Models\Language::select('code', 'name', 'direction', 'is_default', 'status')
                ->get()
                ->toArray();
        });
    }

    public function getCurrencies()
    {
        return Cache::remember('currencies', self::TTL_STATIC_DATA, function () {
            return \Modules\Currency\app\Models\MultiCurrency::all()->toArray();
        });
    }

    public function getCountries()
    {
        return Cache::remember('countries', self::TTL_STATIC_DATA, function () {
            return \Modules\Location\app\Models\Country::select('id', 'name')
                ->where('status', 1)
                ->get()
                ->toArray();
        });
    }

    public function getSocialLinks()
    {
        return Cache::remember('social_links', self::TTL_STATIC_DATA, function () {
            return \Modules\SocialLink\app\Models\SocialLink::select('icon', 'link')
                ->get()
                ->toArray();
        });
    }

    public function getCourseLanguages($limit = -1)
    {
        $cacheKey = "course_languages_{$limit}";
        
        return Cache::remember($cacheKey, self::TTL_STATIC_DATA, function () use ($limit) {
            $query = \Modules\Course\app\Models\CourseLanguage::select('id', 'name')
                ->orderBy('name')
                ->where('status', 1)
                ->latest();

            if ($limit > 0) {
                $query->take($limit);
            }

            return $query->get()->toArray();
        });
    }

    public function getCourseLevels($languageCode = 'en', $limit = -1)
    {
        $cacheKey = "course_levels_{$languageCode}_{$limit}";
        
        return Cache::remember($cacheKey, self::TTL_STATIC_DATA, function () use ($languageCode, $limit) {
            $query = \Modules\Course\app\Models\CourseLevel::select('id', 'slug')
                ->with(['translations' => function ($q) use ($languageCode) {
                    $q->where('lang_code', $languageCode)->select('course_level_id', 'name');
                }])
                ->orderBy('slug')
                ->where('status', 1)
                ->latest();

            if ($limit > 0) {
                $query->take($limit);
            }

            return $query->get()->toArray();
        });
    }

    /**
     * Clear all application cache
     */
    public function clearAllCache()
    {
        Cache::flush();
    }

    /**
     * Get cache statistics (for monitoring)
     */
    public function getCacheStats()
    {
        return [
            'driver' => config('cache.default'),
            'prefix' => config('cache.prefix'),
            'stores' => array_keys(config('cache.stores')),
        ];
    }
}
