<?php

use App\Services\Taxonomy\TaxonomyCatalogRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Service\CourseCategoryCacheClear;

return new class extends Migration
{
    public function up(): void
    {
        $rootId = DB::table('course_categories')
            ->whereNull('parent_id')
            ->where('taxonomy_profile', 'cdacc')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->value('id');
        if (! $rootId) {
            return;
        }

        $departmentIds = DB::table('course_categories')
            ->where('parent_id', $rootId)
            ->whereNull('deleted_at')
            ->pluck('id');
        DB::table('course_categories')->whereIn('id', $departmentIds)->update([
            'node_type' => 'department',
            'taxonomy_profile' => 'cdacc',
            'updated_at' => now(),
        ]);

        $programmeIds = DB::table('course_categories')
            ->whereIn('parent_id', $departmentIds)
            ->whereNull('deleted_at')
            ->pluck('id');
        DB::table('course_categories')->whereIn('id', $programmeIds)->update([
            'node_type' => 'programme',
            'taxonomy_profile' => 'cdacc',
            'updated_at' => now(),
        ]);

        $metadata = json_decode((string) DB::table('course_categories')->where('id', $rootId)
            ->value('taxonomy_metadata'), true) ?: [];
        $metadata['admin_taxonomy_version'] = 3;
        $metadata['legacy_depth_normalized'] = true;
        DB::table('course_categories')->where('id', $rootId)->update([
            'taxonomy_metadata' => json_encode($metadata),
            'updated_at' => now(),
        ]);

        CourseCategoryCacheClear::clear();
        app(TaxonomyCatalogRepository::class)->clearCache();
    }

    public function down(): void
    {
        // Legacy types cannot be reconstructed reliably; preserve the normalized hierarchy.
    }
};
