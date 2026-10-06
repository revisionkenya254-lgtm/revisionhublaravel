<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->where('key', 'app_name')
                ->update(['value' => 'RevisionHubKenya']);
        }

        $this->replaceBrandInColumn('sections', 'global_content');
        $this->replaceBrandInColumn('section_translations', 'content');
        $this->replaceBrandInColumn('custom_page_translations', 'name');
        $this->replaceBrandInColumn('custom_page_translations', 'content');
        $this->replaceBrandInColumn('custom_page_translations', 'seo_title');
        $this->replaceBrandInColumn('custom_page_translations', 'seo_description');
    }

    public function down(): void
    {
        // Brand migrations are intentionally not reversible.
    }

    private function replaceBrandInColumn(string $table, string $column): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }

        DB::table($table)
            ->where($column, 'like', '%SkillGro%')
            ->orWhere($column, 'like', '%Skillgro%')
            ->orWhere($column, 'like', '%Skillgrow%')
            ->orWhere($column, 'like', '%SkillGrow%')
            ->orWhere($column, 'like', '%skillgro%')
            ->orWhere($column, 'like', '%skill_grow%')
            ->update([
                $column => DB::raw(
                    "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE($column, 'SkillGro', 'RevisionHubKenya'), 'Skillgro', 'RevisionHubKenya'), 'Skillgrow', 'RevisionHubKenya'), 'SkillGrow', 'RevisionHubKenya'), 'skillgro', 'revisionhubkenya'), 'skill_grow', 'revisionhubkenya')"
                ),
            ]);
    }
};
