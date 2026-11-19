<?php

use App\Services\Taxonomy\TaxonomyCatalogRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Course\app\Service\CourseCategoryCacheClear;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rootId = $this->node(null, 'cdacc', 'CDACC', 'education_level', 'cdacc', 8, null, $now);
        $departmentId = $this->node(
            $rootId,
            'agriculture-and-aquaculture-department',
            'Agriculture and Aquaculture Department',
            'department',
            'cdacc',
            0,
            null,
            $now
        );
        $programmeId = $this->node(
            $departmentId,
            'agricultural-engineering-level-6',
            'Agricultural Engineering Level 6',
            'programme',
            'cdacc',
            0,
            ['qualification_level' => 'Level 6'],
            $now
        );

        $communicationId = $this->unit(
            $rootId,
            'CDACC-CU-001',
            'Communication Skills',
            'communication-skills',
            $now
        );
        $renewableEnergyId = $this->unit(
            $rootId,
            'CDACC-AE-601',
            'Apply Renewable Energy Technology in Agriculture',
            'apply-renewable-energy-technology-in-agriculture',
            $now
        );

        $this->assignment($programmeId, $communicationId, 'common', 'Module 1', 0, $now);
        $this->assignment($programmeId, $renewableEnergyId, 'major', 'Module 1', 1, $now);

        CourseCategoryCacheClear::clear();
        app(TaxonomyCatalogRepository::class)->clearCache();
    }

    public function down(): void
    {
        // This migration repairs shared taxonomy records that may predate it.
        // Preserve those records on rollback rather than deleting user-linked data.
    }

    private function node(
        ?int $parentId,
        string $slug,
        string $name,
        string $nodeType,
        string $profile,
        int $order,
        ?array $metadata,
        $now
    ): int {
        $query = DB::table('course_categories')->where('slug', $slug)->whereNull('deleted_at');
        $parentId === null ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId);
        $id = $query->value('id');
        $values = [
            'parent_id' => $parentId,
            'slug' => $slug,
            'node_type' => $nodeType,
            'taxonomy_profile' => $profile,
            'taxonomy_metadata' => $metadata ? json_encode($metadata) : null,
            'order' => $order,
            'status' => true,
            'deleted_at' => null,
            'updated_at' => $now,
        ];

        if ($id) {
            DB::table('course_categories')->where('id', $id)->update($values);
        } else {
            $id = DB::table('course_categories')->insertGetId($values + ['created_at' => $now]);
        }

        $translation = DB::table('course_category_translations')
            ->where('course_category_id', $id)
            ->where('lang_code', 'en');
        if ($translation->exists()) {
            $translation->update(['name' => $name, 'updated_at' => $now]);
        } else {
            DB::table('course_category_translations')->insert([
                'course_category_id' => $id,
                'lang_code' => 'en',
                'name' => $name,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return (int) $id;
    }

    private function unit(int $rootId, string $code, string $name, string $slug, $now): int
    {
        $identity = [
            'awarding_body_id' => $rootId,
            'slug' => $slug,
            'curriculum_version' => 'current',
        ];
        $id = DB::table('curriculum_units')->where($identity)->value('id');
        $values = [
            'code' => $code,
            'name' => $name,
            'status' => true,
            'deleted_at' => null,
            'updated_at' => $now,
        ];

        if ($id) {
            DB::table('curriculum_units')->where('id', $id)->update($values);
        } else {
            $id = DB::table('curriculum_units')->insertGetId($identity + $values + ['created_at' => $now]);
        }

        return (int) $id;
    }

    private function assignment(int $programmeId, int $unitId, string $group, string $module, int $order, $now): void
    {
        DB::table('programme_unit_assignments')->updateOrInsert(
            ['programme_node_id' => $programmeId, 'curriculum_unit_id' => $unitId],
            [
                'group_type' => $group,
                'study_year' => null,
                'semester' => null,
                'module' => $module,
                'stage' => null,
                'section' => null,
                'is_mandatory' => true,
                'display_order' => $order,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
};
