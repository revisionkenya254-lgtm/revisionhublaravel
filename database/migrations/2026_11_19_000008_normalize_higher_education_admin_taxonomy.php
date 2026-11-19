<?php

use App\Services\Taxonomy\TaxonomyCatalogRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Service\CourseCategoryCacheClear;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('higher_education_taxonomy.profiles', []) as $profile => $definition) {
            $root = DB::table('course_categories')
                ->whereNull('parent_id')
                ->where('taxonomy_profile', $profile)
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->first();
            if (! $root) {
                continue;
            }

            $metadata = json_decode((string) ($root->taxonomy_metadata ?? ''), true) ?: [];
            $metadata['admin_taxonomy_version'] = 2;
            $metadata['hierarchy'] = array_values($definition['hierarchy'] ?? []);
            DB::table('course_categories')->where('id', $root->id)->update([
                'node_type' => 'education_level',
                'taxonomy_profile' => $profile,
                'taxonomy_metadata' => json_encode($metadata),
                'status' => true,
                'updated_at' => now(),
            ]);

            $pending = collect([(int) $root->id]);
            while ($pending->isNotEmpty()) {
                $children = DB::table('course_categories')
                    ->whereIn('parent_id', $pending)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id);
                if ($children->isEmpty()) {
                    break;
                }
                DB::table('course_categories')->whereIn('id', $children)->update([
                    'taxonomy_profile' => $profile,
                    'updated_at' => now(),
                ]);
                $pending = $children;
            }
        }

        CourseCategoryCacheClear::clear();
        app(TaxonomyCatalogRepository::class)->clearCache();
    }

    public function down(): void
    {
        // Profile inheritance repairs are intentionally preserved on rollback.
    }
};
