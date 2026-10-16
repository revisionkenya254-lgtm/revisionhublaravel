<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_categories', function (Blueprint $table) {
            $table->string('node_type', 40)->nullable()->after('parent_id');
            $table->softDeletes();

            $table->index(
                ['parent_id', 'status', 'order'],
                'course_categories_hierarchy_index'
            );
            $table->index(
                ['node_type', 'status', 'order'],
                'course_categories_type_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('course_categories', function (Blueprint $table) {
            $table->dropIndex('course_categories_type_index');
            $table->dropIndex('course_categories_hierarchy_index');
            $table->dropSoftDeletes();
            $table->dropColumn('node_type');
        });
    }
};
