<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $rootId = $this->node(null, 'cdacc', 'CDACC', 'education_level', 12, 'cdacc', null, $now);
        $departmentId = $this->node(
            $rootId,
            'agriculture-and-aquaculture-department',
            'Agriculture and Aquaculture Department',
            'department',
            0,
            'cdacc',
            null,
            $now
        );
        $programmeId = $this->node(
            $departmentId,
            'agricultural-engineering-level-6',
            'Agricultural Engineering – Level 6',
            'programme',
            0,
            'cdacc',
            ['qualification_level' => 'Level 6'],
            $now
        );

        $communicationId = $this->unit(
            $rootId,
            'communication-skills',
            'Communication Skills',
            null,
            'current',
            $now
        );
        $renewableId = $this->unit(
            $rootId,
            'apply-renewable-energy-technology-in-agriculture',
            'Apply Renewable Energy Technology in Agriculture',
            null,
            'current',
            $now
        );

        $this->assignment($programmeId, $communicationId, 'common', 0, $now);
        $this->assignment($programmeId, $renewableId, 'major', 0, $now);
    }

    public function down(): void
    {
        $rootId = DB::table('course_categories')->whereNull('parent_id')->where('slug', 'cdacc')->value('id');
        if (! $rootId) {
            return;
        }
        $unitIds = DB::table('curriculum_units')->where('awarding_body_id', $rootId)->pluck('id');
        DB::table('programme_unit_assignments')->whereIn('curriculum_unit_id', $unitIds)->delete();
        DB::table('curriculum_units')->whereIn('id', $unitIds)->delete();
        $departmentIds = DB::table('course_categories')->where('parent_id', $rootId)->pluck('id');
        DB::table('course_categories')->whereIn('parent_id', $departmentIds)->delete();
        DB::table('course_category_translations')->whereIn('course_category_id', $departmentIds)->delete();
        DB::table('course_categories')->whereIn('id', $departmentIds)->delete();
        DB::table('course_category_translations')->where('course_category_id', $rootId)->delete();
        DB::table('course_categories')->where('id', $rootId)->delete();
    }

    private function node(
        ?int $parentId,
        string $slug,
        string $name,
        string $type,
        int $order,
        string $profile,
        ?array $metadata,
        $now
    ): int {
        $query = DB::table('course_categories')->where('slug', $slug);
        $parentId === null ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId);
        $id = $query->value('id');
        if (! $id) {
            $id = DB::table('course_categories')->insertGetId([
                'parent_id' => $parentId,
                'slug' => $slug,
                'node_type' => $type,
                'taxonomy_profile' => $profile,
                'taxonomy_metadata' => $metadata ? json_encode($metadata) : null,
                'order' => $order,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('course_categories')->where('id', $id)->update([
                'node_type' => $type,
                'taxonomy_profile' => $profile,
                'taxonomy_metadata' => $metadata ? json_encode($metadata) : null,
                'updated_at' => $now,
            ]);
        }
        DB::table('course_category_translations')->updateOrInsert(
            ['course_category_id' => $id, 'lang_code' => 'en'],
            ['name' => $name, 'created_at' => $now, 'updated_at' => $now]
        );
        return (int) $id;
    }

    private function unit(int $rootId, string $slug, string $name, ?string $code, string $version, $now): int
    {
        $id = DB::table('curriculum_units')->where([
            'awarding_body_id' => $rootId,
            'slug' => $slug,
            'curriculum_version' => $version,
        ])->value('id');
        if ($id) {
            return (int) $id;
        }
        return (int) DB::table('curriculum_units')->insertGetId([
            'awarding_body_id' => $rootId,
            'code' => $code,
            'name' => $name,
            'slug' => $slug,
            'curriculum_version' => $version,
            'status' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function assignment(int $programmeId, int $unitId, string $group, int $order, $now): void
    {
        DB::table('programme_unit_assignments')->updateOrInsert(
            ['programme_node_id' => $programmeId, 'curriculum_unit_id' => $unitId],
            [
                'group_type' => $group,
                'is_mandatory' => true,
                'display_order' => $order,
                'status' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
    }
};
