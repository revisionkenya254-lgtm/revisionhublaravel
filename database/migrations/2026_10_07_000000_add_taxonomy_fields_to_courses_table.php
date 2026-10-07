<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('education_level')->nullable()->after('category_id');
            $table->string('class_grade')->nullable()->after('education_level');
            $table->string('subject')->nullable()->after('class_grade');
            $table->string('exam_category')->nullable()->after('subject');
            $table->unsignedSmallInteger('academic_year')->nullable()->after('exam_category');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['education_level', 'class_grade', 'subject', 'exam_category', 'academic_year']);
        });
    }
};
