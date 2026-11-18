<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `programme_unit_assignments` MODIFY `group_type` VARCHAR(40) NOT NULL DEFAULT 'major'");
        }

        Schema::table('programme_unit_assignments', function (Blueprint $table) {
            $table->string('study_year', 40)->nullable()->after('group_type');
            $table->string('module', 80)->nullable()->after('semester');
            $table->string('stage', 80)->nullable()->after('module');
            $table->string('section', 80)->nullable()->after('stage');
            $table->index(
                ['programme_node_id', 'study_year', 'semester'],
                'programme_unit_assignment_period_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('programme_unit_assignments', function (Blueprint $table) {
            $table->dropIndex('programme_unit_assignment_period_index');
            $table->dropColumn(['study_year', 'module', 'stage', 'section']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::table('programme_unit_assignments')
                ->whereNotIn('group_type', ['common', 'major', 'elective'])
                ->update(['group_type' => 'major']);
            DB::statement("ALTER TABLE `programme_unit_assignments` MODIFY `group_type` ENUM('common','major','elective') NOT NULL DEFAULT 'major'");
        }
    }
};
