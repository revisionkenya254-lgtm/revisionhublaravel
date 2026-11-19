<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX = 'course_category_translations_category_language_unique';

    public function up(): void
    {
        if (! Schema::hasTable('course_category_translations')) {
            return;
        }

        DB::table('course_category_translations')
            ->select('course_category_id', 'lang_code')
            ->selectRaw('MIN(id) as keeper_id')
            ->groupBy('course_category_id', 'lang_code')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('course_category_id')
            ->get()
            ->each(function ($duplicate): void {
                DB::table('course_category_translations')
                    ->where('course_category_id', $duplicate->course_category_id)
                    ->where('lang_code', $duplicate->lang_code)
                    ->where('id', '!=', $duplicate->keeper_id)
                    ->delete();
            });

        if (! Schema::hasIndex('course_category_translations', self::INDEX)) {
            Schema::table('course_category_translations', function (Blueprint $table): void {
                $table->unique(['course_category_id', 'lang_code'], self::INDEX);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('course_category_translations')
            && Schema::hasIndex('course_category_translations', self::INDEX)) {
            Schema::table('course_category_translations', function (Blueprint $table): void {
                $table->dropUnique(self::INDEX);
            });
        }
    }
};
