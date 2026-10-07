<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Service\CourseCategoryCacheClear;

return new class extends Migration
{
    private const REMOVED_SLUGS = [
        'kasneb',
        'cpa',
        'cs',
        'cifa',
        'ccp',
        'cams',
    ];

    public function up(): void
    {
        $this->setStatus(0);
        CourseCategoryCacheClear::clear();
    }

    public function down(): void
    {
        $this->setStatus(1);
        CourseCategoryCacheClear::clear();
    }

    private function setStatus(int $status): void
    {
        $professionalCoursesId = DB::table('course_categories')
            ->where('slug', 'professional-courses')
            ->whereNull('parent_id')
            ->value('id');

        if (! $professionalCoursesId) {
            return;
        }

        DB::table('course_categories')
            ->where('parent_id', $professionalCoursesId)
            ->whereIn('slug', self::REMOVED_SLUGS)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);
    }
};
