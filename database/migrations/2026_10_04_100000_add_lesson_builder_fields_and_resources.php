<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('course_chapter_lessons', function (Blueprint $table) {
            $table->string('lecture_number', 40)->nullable()->after('title');
            $table->string('video_original_name')->nullable()->after('file_path');
            $table->unsignedBigInteger('video_size')->nullable()->after('video_original_name');
            $table->string('video_mime_type', 100)->nullable()->after('video_size');
            $table->boolean('include_in_curriculum')->default(true)->after('is_free');
            $table->boolean('qna_enabled')->default(true)->after('include_in_curriculum');
            $table->boolean('qna_allow_questions')->default(true)->after('qna_enabled');
            $table->boolean('qna_allow_replies')->default(true)->after('qna_allow_questions');
            $table->boolean('qna_notify_instructor')->default(true)->after('qna_allow_replies');
            $table->text('qna_instructions')->nullable()->after('qna_notify_instructor');
        });

        Schema::create('course_lesson_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('course_chapter_lessons')->cascadeOnDelete();
            $table->string('name');
            $table->string('file_path');
            $table->string('storage', 40)->default('bunny_storage');
            $table->string('mime_type', 150)->nullable();
            $table->string('extension', 20)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedInteger('order')->default(0);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('uploaded_by_type', 20)->default('instructor');
            $table->unsignedBigInteger('uploaded_by_id')->nullable();
            $table->timestamps();

            $table->index(['lesson_id', 'status', 'order'], 'lesson_resources_listing_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_lesson_resources');

        Schema::table('course_chapter_lessons', function (Blueprint $table) {
            $table->dropColumn([
                'lecture_number',
                'video_original_name',
                'video_size',
                'video_mime_type',
                'include_in_curriculum',
                'qna_enabled',
                'qna_allow_questions',
                'qna_allow_replies',
                'qna_notify_instructor',
                'qna_instructions',
            ]);
        });
    }
};
