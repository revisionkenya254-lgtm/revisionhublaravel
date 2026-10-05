<?php

namespace Modules\Course\app\Helper;

use App\Enums\ThemeList;
use App\Models\Course;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Helper\CourseCategoryHelper;
use Modules\Frontend\app\Models\FeaturedCourseSection;

class CourseHelper {
    public static function featuredCourses(string $theme_name) {
        $featuredCourse = Cache::rememberForever('featured_course_sections', function () {
            return FeaturedCourseSection::first();
        });

        // Optimize: Fetch all courses and categories in single queries instead of N+1
        $categoryData = collect();
        $coursesByCategory = [
            'all'           => [],
            'categoryOne'   => [],
            'categoryTwo'   => [],
            'categoryThree' => [],
            'categoryFour'  => [],
            'categoryFive'  => [],
        ];

        if ($featuredCourse) {
            // Collect all category IDs and course IDs
            $categoryIds = [];

            if ($featuredCourse->category_one && $featuredCourse->category_one_status == 1) {
                $categoryIds[] = $featuredCourse->category_one;
            }
            if ($featuredCourse->category_two && $featuredCourse->category_two_status == 1) {
                $categoryIds[] = $featuredCourse->category_two;
            }
            if ($featuredCourse->category_three && $featuredCourse->category_three_status == 1) {
                $categoryIds[] = $featuredCourse->category_three;
            }
            if ($featuredCourse->category_four && $featuredCourse->category_four_status == 1) {
                $categoryIds[] = $featuredCourse->category_four;
            }
            if ($featuredCourse->category_five && $featuredCourse->category_five_status == 1) {
                $categoryIds[] = $featuredCourse->category_five;
            }

            // Fetch all categories in one query (select translation_name) and cache per locale
            if (!empty($categoryIds)) {
                $categoryData = CourseCategoryHelper::getAll()->whereIn('id', $categoryIds)->keyBy('id');
            }

            // Collect all course IDs from all categories
            $allCoursesIds = json_decode($featuredCourse?->all_category_ids ? $featuredCourse->all_category_ids : '[]', true);
            $categoryOneIds = json_decode($featuredCourse->category_one_ids ?? '[]', true);
            $categoryTwoIds = json_decode($featuredCourse->category_two_ids ?? '[]', true);
            $categoryThreeIds = json_decode($featuredCourse->category_three_ids ?? '[]', true);
            $categoryFourIds = json_decode($featuredCourse->category_four_ids ?? '[]', true);
            $categoryFiveIds = json_decode($featuredCourse->category_five_ids ?? '[]', true);

            $allCourseIds = array_unique(array_merge(
                $allCoursesIds,
                $categoryOneIds ?? [],
                $categoryTwoIds ?? [],
                $categoryThreeIds ?? [],
                $categoryFourIds ?? [],
                $categoryFiveIds ?? []
            ));

            // Fetch all courses in one query with necessary relations (avoid eager-loading category.translation per course)
            $allCoursesCollectionById = [];
            if (!empty($allCourseIds)) {
                // Build relations array conditionally
                $relations = [
                    'favoriteBy',
                    'instructor:id,name',
                ];
                if ($theme_name == ThemeList::LANGUAGE->value) {
                    $relations[] = 'languages.language:id,name';
                }
                $allCourses = Course::with($relations)
                    ->whereIn('id', $allCourseIds)->where(['is_approved' => 'approved', 'status' => 'active'])
                    ->withCount([
                        'reviews as avg_rating' => function ($query) {
                            $query->select(DB::raw('coalesce(avg(rating), 0)'));
                        },
                    ])
                    ->withCount('enrollments')
                    ->withCount('lessons')
                    ->get();

                // Collect category ids referenced by these courses and fetch their translations once
                $courseCategoryIds = $allCourses->pluck('category_id')->unique()->filter()->values()->all();
                $categoriesForCourses = collect();
                if (!empty($courseCategoryIds)) {
                    $categoriesForCourses = CourseCategoryHelper::getAll()->whereIn('id', $courseCategoryIds)->keyBy('id');
                }

                // Attach translation_name to each course and its category, then key by id
                $allCoursesCollectionById = $allCourses->mapWithKeys(function ($course) use ($categoriesForCourses) {
                    $name = $categoriesForCourses[$course->category_id]->translation_name ?? $categoriesForCourses[$course->category_id]->name ?? null;
                    $course->category_translation_name = $name;
                    return [$course->id => $course];
                })->all();
            }

            // Map each course to its category
            foreach ($allCoursesIds as $courseId) {
                if (isset($allCoursesCollectionById[$courseId])) {
                    $coursesByCategory['all'][] = $allCoursesCollectionById[$courseId];
                }
            }
            foreach ($categoryOneIds as $courseId) {
                if (isset($allCoursesCollectionById[$courseId])) {
                    $coursesByCategory['categoryOne'][] = $allCoursesCollectionById[$courseId];
                }
            }
            foreach ($categoryTwoIds as $courseId) {
                if (isset($allCoursesCollectionById[$courseId])) {
                    $coursesByCategory['categoryTwo'][] = $allCoursesCollectionById[$courseId];
                }
            }
            foreach ($categoryThreeIds as $courseId) {
                if (isset($allCoursesCollectionById[$courseId])) {
                    $coursesByCategory['categoryThree'][] = $allCoursesCollectionById[$courseId];
                }
            }
            foreach ($categoryFourIds as $courseId) {
                if (isset($allCoursesCollectionById[$courseId])) {
                    $coursesByCategory['categoryFour'][] = $allCoursesCollectionById[$courseId];
                }
            }
            foreach ($categoryFiveIds as $courseId) {
                if (isset($allCoursesCollectionById[$courseId])) {
                    $coursesByCategory['categoryFive'][] = $allCoursesCollectionById[$courseId];
                }
            }
        }

        return [
            'featuredCourse'    => $featuredCourse,
            'categoryData'      => $categoryData,
            'coursesByCategory' => $coursesByCategory,
        ];
    }
}