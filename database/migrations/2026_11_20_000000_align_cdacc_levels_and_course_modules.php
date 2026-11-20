<?php

use App\Services\Taxonomy\TaxonomyCatalogRepository;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Course\app\Service\CourseCategoryCacheClear;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('programme_unit_assignments')
            || ! Schema::hasColumn('programme_unit_assignments', 'study_year')
            || ! Schema::hasColumn('programme_unit_assignments', 'module')) {
            return;
        }

        $assignments = DB::table('programme_unit_assignments as assignment')
            ->join('course_categories as programme', 'programme.id', '=', 'assignment.programme_node_id')
            ->leftJoin('course_category_translations as translation', function ($join) {
                $join->on('translation.course_category_id', '=', 'programme.id')
                    ->where('translation.lang_code', '=', 'en');
            })
            ->whereIn('programme.taxonomy_profile', ['cdacc', 'certificate', 'diploma'])
            ->select([
                'assignment.id', 'assignment.study_year', 'assignment.semester', 'assignment.module',
                'programme.taxonomy_profile', 'programme.taxonomy_metadata', 'translation.name as programme_name',
            ])->get();

        foreach ($assignments as $assignment) {
            if ($assignment->taxonomy_profile === 'cdacc' && blank($assignment->study_year)) {
                $level = $this->cdaccLevel($assignment->taxonomy_metadata, $assignment->programme_name);
                if ($level) {
                    DB::table('programme_unit_assignments')->where('id', $assignment->id)->update([
                        'study_year' => $level,
                        'updated_at' => now(),
                    ]);
                }
                continue;
            }

            if (in_array($assignment->taxonomy_profile, ['certificate', 'diploma'], true)
                && blank($assignment->module)) {
                $module = $this->moduleFromLegacyPeriod($assignment->study_year, $assignment->semester);
                if ($module) {
                    DB::table('programme_unit_assignments')->where('id', $assignment->id)->update([
                        'module' => $module,
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        CourseCategoryCacheClear::clear();
        app(TaxonomyCatalogRepository::class)->clearCache();
    }

    public function down(): void
    {
        // Legacy period values are deliberately retained, so rollback needs no data rewrite.
    }

    private function cdaccLevel(mixed $metadata, ?string $programmeName): ?string
    {
        $metadata = is_array($metadata) ? $metadata : json_decode((string) $metadata, true);
        $qualification = trim((string) ($metadata['qualification_level'] ?? ''));
        foreach ([$qualification, (string) $programmeName] as $candidate) {
            if (preg_match('/\blevel\s*([0-9]+)\b/i', $candidate, $matches)) {
                return 'Level '.$matches[1];
            }
        }

        return null;
    }

    private function moduleFromLegacyPeriod(?string $studyYear, ?string $semester): ?string
    {
        foreach ([$studyYear, $semester] as $candidate) {
            if (preg_match('/\b(?:year|semester|module)\s*([0-9]+)\b/i', (string) $candidate, $matches)) {
                return 'Module '.$matches[1];
            }
        }

        return null;
    }
};
