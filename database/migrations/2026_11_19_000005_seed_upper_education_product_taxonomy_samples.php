<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $profiles = [
            [
                'profile' => 'tvet', 'root_slug' => 'tvet', 'root_name' => 'TVET', 'order' => 7,
                'owner' => ['Technical and Vocational Training Authority', 'tveta-sample'],
                'department' => ['Electrical and Electronics Trade Area', 'electrical-electronics-trade-area'],
                'programme' => ['Electrical Installation - Level 5', 'electrical-installation-level-5', ['qualification_level' => 'Level 5']],
                'units' => [
                    ['Communication Skills', 'communication-skills', 'TVT-COM-101', 'common', ['module' => 'Module 1']],
                    ['Electrical Installation Practice', 'electrical-installation-practice', 'TVT-ELI-105', 'major', ['module' => 'Module 1']],
                ],
            ],
            [
                'profile' => 'certificate', 'root_slug' => 'certificate-courses', 'root_name' => 'Certificate', 'order' => 9,
                'owner' => ['Kenya Sample Technical College', 'kenya-sample-technical-college-certificate'],
                'department' => ['School of ICT and Computing', 'school-of-ict-computing-certificate'],
                'programme' => ['Certificate in Information Communication Technology', 'certificate-in-ict', ['qualification_level' => 'Certificate']],
                'units' => [
                    ['Communication Skills', 'communication-skills', 'CICT-101', 'common', ['module' => 'Module 1', 'semester' => 'Semester 1']],
                    ['Computer Applications', 'computer-applications', 'CICT-102', 'major', ['module' => 'Module 1', 'semester' => 'Semester 1']],
                ],
            ],
            [
                'profile' => 'diploma', 'root_slug' => 'diploma-courses', 'root_name' => 'Diploma', 'order' => 10,
                'owner' => ['Kenya Sample Technical College', 'kenya-sample-technical-college-diploma'],
                'department' => ['School of Engineering', 'school-of-engineering-diploma'],
                'programme' => ['Diploma in Electrical Engineering', 'diploma-in-electrical-engineering', ['qualification_level' => 'Diploma']],
                'units' => [
                    ['Communication Skills', 'communication-skills', 'DEE-1101', 'common', ['study_year' => 'Year 1', 'semester' => 'Semester 1']],
                    ['Electrical Circuit Theory', 'electrical-circuit-theory', 'DEE-1102', 'major', ['study_year' => 'Year 1', 'semester' => 'Semester 1']],
                ],
            ],
            [
                'profile' => 'undergraduate', 'root_slug' => 'undergraduate', 'root_name' => 'Undergraduate', 'order' => 11,
                'owner' => ['RevisionHub Sample University', 'revisionhub-sample-university'],
                'department' => ['Faculty of Computing and Information Technology', 'faculty-of-computing-information-technology'],
                'programme' => ['Bachelor of Science in Computer Science', 'bsc-computer-science', ['qualification_level' => 'Bachelor Degree']],
                'units' => [
                    ['Communication Skills', 'communication-skills', 'UCU-100', 'university_common', ['study_year' => 'Year 1', 'semester' => 'Semester 1']],
                    ['Introduction to Programming', 'introduction-to-programming', 'CSC-101', 'core', ['study_year' => 'Year 1', 'semester' => 'Semester 1']],
                ],
            ],
            [
                'profile' => 'professional', 'root_slug' => 'professional-courses', 'root_name' => 'Professional', 'order' => 12,
                'owner' => ['KASNEB', 'kasneb-sample'],
                'department' => ['Certified Public Accountants Qualification', 'cpa-qualification'],
                'programme' => ['Certified Public Accountants (CPA)', 'certified-public-accountants-cpa', ['qualification_level' => 'Professional Qualification']],
                'units' => [
                    ['Financial Accounting', 'financial-accounting', 'CPA-FA', 'paper', ['stage' => 'Foundation Level', 'section' => 'Section 1']],
                    ['Communication Skills', 'communication-skills', 'CPA-CS', 'paper', ['stage' => 'Foundation Level', 'section' => 'Section 1']],
                ],
            ],
        ];

        $cdaccId = $this->root('cdacc', 'CDACC', 'cdacc', 8, $now);
        $this->mergeDuplicateCdaccRoots($cdaccId, $now);

        foreach ($profiles as $definition) {
            $rootId = $this->root(
                $definition['root_slug'],
                $definition['root_name'],
                $definition['profile'],
                $definition['order'],
                $now
            );
            $ownerId = $this->node($rootId, $definition['owner'][1], $definition['owner'][0], 'awarding_body', $definition['profile'], 0, null, $now);
            $departmentId = $this->node($ownerId, $definition['department'][1], $definition['department'][0], 'department', $definition['profile'], 0, null, $now);
            $programmeId = $this->node($departmentId, $definition['programme'][1], $definition['programme'][0], 'programme', $definition['profile'], 0, $definition['programme'][2], $now);

            foreach ($definition['units'] as $index => [$name, $slug, $code, $group, $periods]) {
                $unitId = $this->unit($ownerId, $name, $slug, $code, $now);
                $this->assignment($programmeId, $unitId, $group, $periods, $index, $now);
            }
        }

        \Modules\Course\app\Service\CourseCategoryCacheClear::clear();
    }

    public function down(): void
    {
        $owners = [
            'tveta-sample', 'kenya-sample-technical-college-certificate',
            'kenya-sample-technical-college-diploma', 'revisionhub-sample-university', 'kasneb-sample',
        ];
        $ownerIds = DB::table('course_categories')->whereIn('slug', $owners)->pluck('id');
        $unitIds = DB::table('curriculum_units')->whereIn('awarding_body_id', $ownerIds)->pluck('id');
        DB::table('programme_unit_assignments')->whereIn('curriculum_unit_id', $unitIds)->delete();
        DB::table('curriculum_units')->whereIn('id', $unitIds)->delete();

        $nodeIds = collect($ownerIds);
        $pending = collect($ownerIds);
        while ($pending->isNotEmpty()) {
            $children = DB::table('course_categories')->whereIn('parent_id', $pending)->pluck('id');
            $nodeIds = $nodeIds->merge($children);
            $pending = $children;
        }
        DB::table('course_category_translations')->whereIn('course_category_id', $nodeIds)->delete();
        DB::table('course_categories')->whereIn('id', $nodeIds->sortDesc()->values())->delete();
    }

    private function root(string $slug, string $name, string $profile, int $order, $now): int
    {
        return $this->node(null, $slug, $name, 'education_level', $profile, $order, null, $now);
    }

    private function node(?int $parentId, string $slug, string $name, string $type, string $profile, int $order, ?array $metadata, $now): int
    {
        $query = DB::table('course_categories')->where('slug', $slug)->whereNull('deleted_at');
        $parentId === null ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId);
        $id = $query->value('id');
        $values = [
            'parent_id' => $parentId, 'slug' => $slug, 'node_type' => $type,
            'taxonomy_profile' => $profile, 'taxonomy_metadata' => $metadata ? json_encode($metadata) : null,
            'order' => $order, 'status' => true, 'updated_at' => $now,
        ];
        if ($id) {
            DB::table('course_categories')->where('id', $id)->update($values);
        } else {
            $id = DB::table('course_categories')->insertGetId($values + ['created_at' => $now]);
        }
        DB::table('course_category_translations')->updateOrInsert(
            ['course_category_id' => $id, 'lang_code' => 'en'],
            ['name' => $name, 'created_at' => $now, 'updated_at' => $now]
        );

        return (int) $id;
    }

    private function unit(int $ownerId, string $name, string $slug, string $code, $now): int
    {
        $identity = ['awarding_body_id' => $ownerId, 'slug' => $slug, 'curriculum_version' => '2026'];
        $id = DB::table('curriculum_units')->where($identity)->value('id');
        $values = ['code' => $code, 'name' => $name, 'status' => true, 'updated_at' => $now, 'deleted_at' => null];
        if ($id) {
            DB::table('curriculum_units')->where('id', $id)->update($values);
            return (int) $id;
        }

        return (int) DB::table('curriculum_units')->insertGetId($identity + $values + ['created_at' => $now]);
    }

    private function assignment(int $programmeId, int $unitId, string $group, array $periods, int $order, $now): void
    {
        DB::table('programme_unit_assignments')->updateOrInsert(
            ['programme_node_id' => $programmeId, 'curriculum_unit_id' => $unitId],
            array_merge([
                'group_type' => $group, 'study_year' => null, 'semester' => null, 'module' => null,
                'stage' => null, 'section' => null, 'is_mandatory' => true, 'display_order' => $order,
                'status' => true, 'created_at' => $now, 'updated_at' => $now,
            ], $periods)
        );
    }

    private function mergeDuplicateCdaccRoots(int $canonicalId, $now): void
    {
        $duplicates = DB::table('course_categories as category')
            ->leftJoin('course_category_translations as translation', function ($join) {
                $join->on('translation.course_category_id', '=', 'category.id')->where('translation.lang_code', 'en');
            })
            ->whereNull('category.parent_id')->whereNull('category.deleted_at')
            ->where('category.id', '!=', $canonicalId)
            ->where(function ($query) {
                $query->whereIn('category.slug', ['cdacc-courses', 'cdacc-course'])
                    ->orWhereRaw('LOWER(TRIM(translation.name)) = ?', ['cdacc courses']);
            })
            ->pluck('category.id');

        foreach ($duplicates as $duplicateId) {
            DB::table('course_categories')->where('parent_id', $duplicateId)->update([
                'parent_id' => $canonicalId, 'taxonomy_profile' => 'cdacc', 'updated_at' => $now,
            ]);
            DB::table('products')->where('category_id', $duplicateId)->update(['category_id' => $canonicalId]);
            if (DB::getSchemaBuilder()->hasTable('courses')) {
                DB::table('courses')->where('category_id', $duplicateId)->update(['category_id' => $canonicalId]);
            }
            DB::table('curriculum_units')->where('awarding_body_id', $duplicateId)->update(['awarding_body_id' => $canonicalId]);
            DB::table('course_categories')->where('id', $duplicateId)->update([
                'status' => false, 'node_type' => 'legacy', 'taxonomy_profile' => null,
                'deleted_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
};
