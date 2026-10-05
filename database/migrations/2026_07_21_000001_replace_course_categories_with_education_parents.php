<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Replace the imported category tree with the education parent categories.
     */
    public function up(): void
    {
        DB::transaction(function () {
            $existingCategoryIds = DB::table('course_categories')->pluck('id')->all();
            $affectedCourseIds = empty($existingCategoryIds)
                ? []
                : DB::table('courses')
                    ->whereIn('category_id', $existingCategoryIds)
                    ->pluck('id')
                    ->all();

            if (! empty($existingCategoryIds)) {
                DB::table('products')
                    ->whereIn('category_id', $existingCategoryIds)
                    ->delete();

                if (! empty($affectedCourseIds)) {
                    $chapterIds = DB::table('course_chapters')
                        ->whereIn('course_id', $affectedCourseIds)
                        ->pluck('id')
                        ->all();

                    if (! empty($chapterIds)) {
                        $chapterItemIds = DB::table('course_chapter_items')
                            ->whereIn('chapter_id', $chapterIds)
                            ->pluck('id')
                            ->all();

                        if (! empty($chapterItemIds)) {
                            DB::table('course_chapter_lessons')
                                ->whereIn('chapter_item_id', $chapterItemIds)
                                ->delete();

                            DB::table('course_chapter_items')
                                ->whereIn('id', $chapterItemIds)
                                ->delete();
                        }

                        DB::table('course_chapters')
                            ->whereIn('id', $chapterIds)
                            ->delete();
                    }

                    DB::table('course_progress')
                        ->whereIn('course_id', $affectedCourseIds)
                        ->delete();

                    DB::table('enrollments')
                        ->whereIn('course_id', $affectedCourseIds)
                        ->delete();

                    DB::table('order_items')
                        ->whereIn('course_id', $affectedCourseIds)
                        ->delete();

                    DB::table('carts')
                        ->whereIn('course_id', $affectedCourseIds)
                        ->delete();

                    DB::table('courses')
                        ->whereIn('id', $affectedCourseIds)
                        ->delete();
                }

                DB::table('featured_course_sections')->update([
                    'all_category' => null,
                    'all_category_ids' => null,
                    'category_one' => null,
                    'category_one_ids' => null,
                    'category_two' => null,
                    'category_two_ids' => null,
                    'category_three' => null,
                    'category_three_ids' => null,
                    'category_four' => null,
                    'category_four_ids' => null,
                    'category_five' => null,
                    'category_five_ids' => null,
                ]);

                DB::table('course_category_translations')->delete();
                DB::table('course_categories')->delete();
            }

            $this->seedEducationParentCategories();
            $this->clearCategoryCaches();
        });
    }

    public function down(): void
    {
        // This migration removes live content and replaces it with the education
        // hierarchy, so a safe automatic rollback is not available.
    }

    private function seedEducationParentCategories(): void
    {
        $categories = [
            ['slug' => 'pre-primary', 'name' => 'Pre-Primary', 'icon' => 'fa-child', 'order' => 1],
            ['slug' => 'lower-primary', 'name' => 'Lower Primary', 'icon' => 'fa-school', 'order' => 2],
            ['slug' => 'upper-primary', 'name' => 'Upper Primary', 'icon' => 'fa-user-graduate', 'order' => 3],
            ['slug' => 'junior-school', 'name' => 'Junior School', 'icon' => 'fa-book-reader', 'order' => 4],
            ['slug' => 'senior-school-cbc', 'name' => 'Senior School (CBC)', 'icon' => 'fa-graduation-cap', 'order' => 5],
            ['slug' => 'high-school', 'name' => 'High School', 'icon' => 'fa-book', 'order' => 6],
            ['slug' => 'tvet', 'name' => 'TVET', 'icon' => 'fa-wrench', 'order' => 7],
            ['slug' => 'certificate-courses', 'name' => 'Certificate Courses', 'icon' => 'fa-certificate', 'order' => 8],
            ['slug' => 'diploma-courses', 'name' => 'Diploma Courses', 'icon' => 'fa-file-alt', 'order' => 9],
            ['slug' => 'undergraduate', 'name' => 'Undergraduate', 'icon' => 'fa-university', 'order' => 10],
            ['slug' => 'professional-courses', 'name' => 'Professional Courses', 'icon' => 'fa-award', 'order' => 11],
            ['slug' => 'teacher-resources', 'name' => 'Teacher Resources', 'icon' => 'fa-book-open', 'order' => 12],
        ];

        foreach ($categories as $category) {
            $categoryId = DB::table('course_categories')->insertGetId([
                'slug' => $category['slug'],
                'icon' => $category['icon'],
                'parent_id' => null,
                'order' => $category['order'],
                'show_at_trending' => 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('course_category_translations')->insert([
                'course_category_id' => $categoryId,
                'lang_code' => 'en',
                'name' => $category['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function clearCategoryCaches(): void
    {
        $languages = DB::table('languages')->pluck('code')->all();

        foreach ($languages as $language) {
            Cache::forget("course_categories_{$language}");
            Cache::forget("course_category_tree_{$language}");
            Cache::forget("trending_categories_{$language}");
            Cache::forget("trending_categories_with_counts_{$language}");
        }
    }
};
