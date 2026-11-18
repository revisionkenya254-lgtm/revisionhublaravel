<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_categories', function (Blueprint $table) {
            $table->string('taxonomy_profile', 40)->nullable()->after('node_type');
            $table->json('taxonomy_metadata')->nullable()->after('taxonomy_profile');
            $table->index(['taxonomy_profile', 'node_type', 'status'], 'course_categories_profile_type_index');
        });

        Schema::create('curriculum_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('awarding_body_id')->constrained('course_categories')->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->string('curriculum_version', 80)->default('current');
            $table->text('description')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['awarding_body_id', 'slug', 'curriculum_version'],
                'curriculum_units_identity_unique'
            );
            $table->index(['awarding_body_id', 'status', 'name'], 'curriculum_units_catalog_index');
        });

        Schema::create('programme_unit_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_node_id')->constrained('course_categories')->cascadeOnDelete();
            $table->foreignId('curriculum_unit_id')->constrained('curriculum_units')->cascadeOnDelete();
            $table->enum('group_type', ['common', 'major', 'elective'])->default('major');
            $table->string('semester', 80)->nullable();
            $table->decimal('credit_hours', 5, 2)->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(
                ['programme_node_id', 'curriculum_unit_id'],
                'programme_unit_assignment_unique'
            );
            $table->index(
                ['programme_node_id', 'group_type', 'status', 'display_order'],
                'programme_unit_assignment_tree_index'
            );
        });

        Schema::create('product_curriculum_contexts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('curriculum_unit_id')->constrained('curriculum_units')->cascadeOnDelete();
            $table->foreignId('programme_unit_assignment_id')->nullable()
                ->constrained('programme_unit_assignments')->nullOnDelete();
            $table->string('resource_type', 40)->default('past_paper');
            $table->string('assessment_series', 80)->nullable();
            $table->unsignedSmallInteger('assessment_year')->nullable();
            $table->string('paper_number', 40)->nullable();
            $table->timestamps();

            $table->unique(
                ['product_id', 'curriculum_unit_id', 'programme_unit_assignment_id'],
                'product_curriculum_context_unique'
            );
            $table->index(
                ['curriculum_unit_id', 'assessment_year', 'assessment_series'],
                'product_curriculum_context_lookup_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_curriculum_contexts');
        Schema::dropIfExists('programme_unit_assignments');
        Schema::dropIfExists('curriculum_units');

        Schema::table('course_categories', function (Blueprint $table) {
            $table->dropIndex('course_categories_profile_type_index');
            $table->dropColumn(['taxonomy_profile', 'taxonomy_metadata']);
        });
    }
};
